<?php

use App\Enums\AksiAkses;
use App\Enums\StatusDokumen;
use App\Models\AccessLog;
use App\Models\Division;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('local');
    Storage::disk('local')->put('dokumen/contoh.pdf', "%PDF-1.4\n%%EOF\n");
    $this->divisi = Division::factory()->create();
    $this->karyawan = User::factory()->karyawan($this->divisi)->create();
});

function idArsip(User $user, string $query = ''): array
{
    return array_column(test()->actingAs($user)->get('/arsip'.$query)->assertOk()->viewData('page')['props']['dokumen']['data'], 'id');
}

it('arsip hanya memuat dokumen disetujui yang terlihat oleh akun', function () {
    $divisiSendiri = Document::factory()->disetujui()->create(['division_id' => $this->divisi->id]);
    $publikLain = Document::factory()->disetujui()->publik()->create();
    Document::factory()->disetujui()->create();
    Document::factory()->status(StatusDokumen::MenungguGm)->create(['division_id' => $this->divisi->id]);
    Document::factory()->create(['division_id' => $this->divisi->id, 'submitted_by' => $this->karyawan->id]);

    expect(idArsip($this->karyawan))->toEqualCanonicalizing([$divisiSendiri->id, $publikLain->id]);
});

it('filter akan kedaluwarsa memuat dokumen dengan sisa <= 90 hari dan belum berakhir', function () {
    $buat = fn ($tgl) => Document::factory()->disetujui()->create(['division_id' => $this->divisi->id, 'tanggal_kadaluarsa' => $tgl])->id;
    $dekat = $buat(today()->addDays(90));
    $hariIni = $buat(today());
    $buat(today()->addDays(91));
    $buat(today()->subDay());
    $buat(null);

    expect(idArsip($this->karyawan, '?kadaluarsa=1'))->toEqualCanonicalizing([$dekat, $hariIni]);
});

it('kata kunci berisi % atau _ tidak menjadi wildcard', function () {
    $persen = Document::factory()->disetujui()->create(['division_id' => $this->divisi->id, 'judul' => 'Diskon 100% tarif']);
    $lain = Document::factory()->disetujui()->create(['division_id' => $this->divisi->id, 'judul' => 'Tarif gas industri']);

    expect(idArsip($this->karyawan, '?q='.urlencode('%')))->toBe([$persen->id])
        ->and(idArsip($this->karyawan, '?q='.urlencode('_')))->toBe([])
        ->and(idArsip($this->karyawan, '?q=TARIF'))->toEqualCanonicalizing([$persen->id, $lain->id]);
});

it('mencari pada nomor dan menyaring jenis serta tahun', function () {
    $a = Document::factory()->disetujui()->create(['division_id' => $this->divisi->id, 'nomor' => 'SK-DIR/007/2023', 'tanggal_penetapan' => '2023-05-01']);
    $b = Document::factory()->disetujui()->create(['division_id' => $this->divisi->id, 'tanggal_penetapan' => '2024-05-01']);

    expect(idArsip($this->karyawan, '?q=sk-dir/007'))->toBe([$a->id])
        ->and(idArsip($this->karyawan, '?tahun=2024'))->toBe([$b->id])
        ->and(idArsip($this->karyawan, '?jenis='.$a->document_type_id.'&tahun=2024'))->toBe([]);
});

it('detail dokumen tak terlihat menghasilkan 403', function () {
    $rahasia = Document::factory()->disetujui()->create();

    $this->actingAs($this->karyawan)->get("/dokumen/{$rahasia->id}")->assertForbidden();
    $this->get('/dokumen/'.$rahasia->id)->assertForbidden();
    expect(AccessLog::count())->toBe(0);
});

it('detail yang terlihat tercatat sebagai lihat', function () {
    $dok = Document::factory()->disetujui()->create(['division_id' => $this->divisi->id]);

    $this->actingAs($this->karyawan)->get("/dokumen/{$dok->id}")->assertInertia(fn (AssertableInertia $page) => $page
        ->component('dokumen/show')->where('dokumen.id', $dok->id)->where('dokumen.boleh_unduh', true));

    $log = AccessLog::sole();
    expect($log->aksi)->toBe(AksiAkses::Lihat)->and($log->user_id)->toBe($this->karyawan->id);
});

it('unduhan dokumen tak terlihat menghasilkan 403', function () {
    $rahasia = Document::factory()->disetujui()->create();

    $this->actingAs($this->karyawan)->get("/dokumen/{$rahasia->id}/berkas/unduh")->assertForbidden();
    $this->actingAs($this->karyawan)->get("/dokumen/{$rahasia->id}/berkas")->assertForbidden();
    $this->get("/dokumen/{$rahasia->id}/berkas/unduh")->assertForbidden();
});

it('admin melihat detail dokumen divisi tetapi tidak bisa mengunduh', function () {
    $dok = Document::factory()->disetujui()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get("/dokumen/{$dok->id}")->assertInertia(fn (AssertableInertia $page) => $page->where('dokumen.boleh_unduh', false));
    $this->actingAs($admin)->get("/dokumen/{$dok->id}/berkas")->assertForbidden();
});

it('guest mengunduh dokumen publik disetujui dan tercatat tanpa user_id', function () {
    $dok = Document::factory()->disetujui()->publik()->create(['berkas_nama' => 'sk-direksi.pdf']);

    $this->get("/dokumen/{$dok->id}/berkas/unduh")->assertOk()->assertDownload('sk-direksi.pdf');

    $log = AccessLog::sole();
    expect($log->aksi)->toBe(AksiAkses::Unduh)->and($log->user_id)->toBeNull();
});

it('pratinjau disajikan inline dan tidak dicatat sebagai unduhan', function () {
    $dok = Document::factory()->disetujui()->publik()->create();

    $res = $this->get("/dokumen/{$dok->id}/berkas")->assertOk();
    expect($res->headers->get('content-disposition'))->toStartWith('inline')
        ->and($res->headers->get('content-type'))->toBe('application/pdf')
        ->and(AccessLog::count())->toBe(0);
});

it('berkas yang hilang dari disk menghasilkan 404, bukan galat server', function () {
    $dok = Document::factory()->disetujui()->publik()->create(['berkas_path' => 'dokumen/tidak-ada.pdf']);

    $this->get("/dokumen/{$dok->id}/berkas/unduh")->assertNotFound();
});

it('relasi ke dokumen yang tidak terlihat tidak ikut ditampilkan', function () {
    $dok = Document::factory()->disetujui()->publik()->create();
    $terlihat = Document::factory()->disetujui()->publik()->create();
    $rahasia = Document::factory()->disetujui()->create();
    $dok->relasiKeluar()->create(['related_document_id' => $terlihat->id, 'jenis' => 'mengubah']);
    $rahasia->relasiKeluar()->create(['related_document_id' => $dok->id, 'jenis' => 'mencabut']);

    $this->get("/dokumen/{$dok->id}")->assertInertia(fn (AssertableInertia $page) => $page
        ->has('dokumen.relasi', 1)->where('dokumen.relasi.0.dokumen.id', $terlihat->id));
});

it('admin tidak punya halaman arsip dan guest diarahkan ke login', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/arsip')->assertForbidden();
    auth()->logout();
    $this->get('/arsip')->assertRedirect('/login');
});

it('arsip menyaring menurut divisi dan akses', function () {
    $gm = User::factory()->gm()->create();
    $publik = Document::factory()->disetujui()->publik()->create();
    $rahasia = Document::factory()->disetujui()->create();

    expect(idArsip($gm, "?divisi={$rahasia->division_id}"))->toBe([$rahasia->id])
        ->and(idArsip($gm, '?akses=publik'))->toBe([$publik->id])
        ->and(idArsip($gm, '?akses=bukan'))->toHaveCount(2);
});
