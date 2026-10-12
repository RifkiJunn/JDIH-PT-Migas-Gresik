<?php

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    config(['ai.gemini.key' => 'kunci-uji', 'ai.gemini.model' => 'model-uji', 'ai.izinkan_dokumen_divisi' => false]);
    $this->karyawan = User::factory()->karyawan()->create();
    $this->jenis = DocumentType::factory()->create(['nama' => 'Keputusan Direksi']);
});

/** Balasan Interactions API yang membungkus $teks sebagai keluaran model. */
function balasanGemini(string $teks): array
{
    return ['status' => 'completed', 'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => $teks]]]]];
}

function ekstrak(string $akses = 'publik')
{
    return test()->actingAs(test()->karyawan)->postJson('/pengajuan/ekstrak', ['berkas' => pdfAsli(), 'akses' => $akses]);
}

it('mengembalikan kolom dari jawaban yang sah', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(balasanGemini(json_encode([
        'judul' => '  Keputusan Direksi tentang Kebijakan K3LL ',
        'nomor' => 'SK-DIR/012/2023',
        'jenis' => 'Keputusan Direksi',
        'tanggal_penetapan' => '2023-08-09',
        'tanggal_kadaluarsa' => null,
        'abstrak' => 'Menetapkan komitmen keselamatan kerja.',
    ])))]);

    ekstrak()->assertOk()->assertExactJson([
        'judul' => 'Keputusan Direksi tentang Kebijakan K3LL',
        'nomor' => 'SK-DIR/012/2023',
        'document_type_id' => $this->jenis->id,
        'tanggal_penetapan' => '2023-08-09',
        'tanggal_kadaluarsa' => null,
        'abstrak' => 'Menetapkan komitmen keselamatan kerja.',
    ]);

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-goog-api-key', 'kunci-uji')
        && $r['model'] === 'model-uji'
        && $r['store'] === false
        && $r['input'][0]['type'] === 'document' && $r['input'][0]['mime_type'] === 'application/pdf'
        && base64_decode($r['input'][0]['data']) === file_get_contents(pdfAsli()->getRealPath())
        && in_array('Keputusan Direksi', $r['response_format']['schema']['properties']['jenis']['enum'], true));
});

it('mengosongkan tanggal yang bukan YYYY-MM-DD dan jenis di luar daftar, kolom lain tetap terisi', function () {
    Http::fake(['*' => Http::response(balasanGemini(json_encode([
        'judul' => 'Judul sah',
        'nomor' => ['bukan', 'teks'],
        'jenis' => 'Jenis Karangan',
        'tanggal_penetapan' => '9 Agustus 2023',
        'tanggal_kadaluarsa' => '2023-02-31',
        'kolom_asing' => 'diabaikan',
    ])))]);

    ekstrak()->assertOk()->assertExactJson([
        'judul' => 'Judul sah', 'nomor' => null, 'document_type_id' => null,
        'tanggal_penetapan' => null, 'tanggal_kadaluarsa' => null, 'abstrak' => null,
    ]);
});

it('memotong teks yang melebihi batas kolom', function () {
    Http::fake(['*' => Http::response(balasanGemini(json_encode(['judul' => str_repeat('a', 400), 'nomor' => str_repeat('n', 300)])))]);

    $json = ekstrak()->assertOk()->json();
    expect(mb_strlen($json['judul']))->toBe(255)->and(mb_strlen($json['nomor']))->toBe(100);
});

it('balasan rusak atau galat penyedia menghasilkan 422 dengan pesan, bukan 500', function (Closure $balasan) {
    Http::fake(['*' => $balasan]);

    ekstrak()->assertStatus(422)->assertJsonStructure(['message']);
})->with([
    'bukan JSON' => [fn () => Http::response(balasanGemini('maaf, saya tidak bisa'))],
    'JSON bukan objek' => [fn () => Http::response(balasanGemini('"teks"'))],
    'tanpa keluaran model' => [fn () => Http::response(['status' => 'failed', 'steps' => []])],
    'galat server' => [fn () => Http::response(['error' => ['message' => 'internal']], 500)],
    'koneksi putus' => [fn () => throw new ConnectionException('timeout')],
]);

it('kuota habis menghasilkan pesan khusus', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'quota']], 429)]);

    ekstrak()->assertStatus(422)->assertJson(['message' => 'Kuota layanan AI sedang habis. Silakan isi manual.']);
});

it('kunci kosong menghasilkan 422 "Isi otomatis belum dikonfigurasi"', function () {
    config(['ai.gemini.key' => null]);
    Http::fake();

    ekstrak()->assertStatus(422)->assertJson(['message' => 'Isi otomatis belum dikonfigurasi.']);
    Http::assertNothingSent();
});

it('akses divisi ditolak 403 saat AI_IZINKAN_DOKUMEN_DIVISI=false dan tidak memanggil penyedia', function () {
    Http::fake();

    ekstrak('divisi')->assertForbidden();
    Http::assertNothingSent();
});

it('akses divisi diizinkan bila AI_IZINKAN_DOKUMEN_DIVISI=true', function () {
    config(['ai.izinkan_dokumen_divisi' => true]);
    Http::fake(['*' => Http::response(balasanGemini('{"judul":"Boleh"}'))]);

    ekstrak('divisi')->assertOk()->assertJsonPath('judul', 'Boleh');
});

it('menolak berkas bukan PDF sebelum memanggil penyedia', function () {
    Http::fake();

    $this->actingAs($this->karyawan)->postJson('/pengajuan/ekstrak', ['berkas' => unggahan('bukan pdf'), 'akses' => 'publik'])
        ->assertStatus(422)->assertJsonValidationErrors('berkas');
    Http::assertNothingSent();
});

it('berkas ekstraksi tidak tertinggal di disk', function () {
    Http::fake(['*' => Http::response(balasanGemini('{"judul":"X"}'))]);

    ekstrak()->assertOk();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('hanya karyawan dan manajer yang boleh memakai isi otomatis', function () {
    Http::fake();

    $this->actingAs(User::factory()->gm()->create())->postJson('/pengajuan/ekstrak', ['berkas' => pdfAsli(), 'akses' => 'publik'])->assertForbidden();
    auth()->logout();
    $this->postJson('/pengajuan/ekstrak', ['berkas' => pdfAsli(), 'akses' => 'publik'])->assertUnauthorized();
});
