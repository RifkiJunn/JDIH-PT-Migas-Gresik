<?php

use App\Enums\StatusDokumen;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('local');
    $this->divisi = Division::factory()->create();
    $this->karyawan = User::factory()->karyawan($this->divisi)->create();
    $this->manajer = User::factory()->manajer($this->divisi)->create();
    $this->jenis = DocumentType::factory()->create();
});

function isian(array $ubah = []): array
{
    return $ubah + [
        'akses' => 'divisi',
        'berkas' => pdfAsli(),
        'judul' => 'Peraturan tentang Uji Coba',
        'nomor' => 'PP/001/2026',
        'document_type_id' => test()->jenis->id,
        'tanggal_penetapan' => '2026-01-15',
        'abstrak' => 'Ringkasan isi dokumen.',
    ];
}

it('menyimpan draf dengan division_id milik pengaju walau request mengirim divisi lain', function () {
    $lain = Division::factory()->create();

    $this->actingAs($this->karyawan)->post('/pengajuan', isian(['division_id' => $lain->id]))->assertRedirect('/pengajuan');

    $dok = Document::sole();
    expect($dok->division_id)->toBe($this->divisi->id)
        ->and($dok->submitted_by)->toBe($this->karyawan->id)
        ->and($dok->status)->toBe(StatusDokumen::Draf)
        ->and($dok->berkas_nama)->toBe('dokumen.pdf');
    Storage::disk('local')->assertExists($dok->berkas_path);
});

it('langsung mengajukan bila diminta', function () {
    $this->actingAs($this->karyawan)->post('/pengajuan', isian(['ajukan' => true]))->assertRedirect('/pengajuan');

    expect(Document::sole()->status)->toBe(StatusDokumen::MenungguManajer);
});

it('mewajibkan tanggal kadaluarsa untuk jenis yang perlu_kadaluarsa', function () {
    $kontrak = DocumentType::factory()->create(['perlu_kadaluarsa' => true]);

    $this->actingAs($this->karyawan)->post('/pengajuan', isian(['document_type_id' => $kontrak->id]))
        ->assertSessionHasErrors('tanggal_kadaluarsa');
    $this->actingAs($this->karyawan)->post('/pengajuan', isian(['document_type_id' => $kontrak->id, 'tanggal_kadaluarsa' => '2025-01-01']))
        ->assertSessionHasErrors('tanggal_kadaluarsa');
    $this->actingAs($this->karyawan)->post('/pengajuan', isian(['document_type_id' => $kontrak->id, 'tanggal_kadaluarsa' => '2030-01-01']))
        ->assertSessionHasNoErrors();
});

it('menolak berkas bukan PDF asli, berkas kosong, dan berkas di atas 20 MB', function (Closure $berkas) {
    $this->actingAs($this->karyawan)->post('/pengajuan', isian(['berkas' => $berkas()]))->assertSessionHasErrors('berkas');

    expect(Document::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBeEmpty();
})->with([
    'ekstensi diganti' => [fn () => unggahan('ini bukan pdf, hanya teks biasa')],
    'kosong' => [fn () => unggahan('')],
    'terlalu besar' => [fn () => unggahan("%PDF-1.4\n".str_repeat('0', 20 * 1024 * 1024 + 2048))],
    'tanpa berkas' => [fn () => null],
]);

it('menolak nomor yang sudah dipakai tetapi mengizinkan nomor draf yang sudah dihapus', function () {
    $lama = Document::factory()->create(['nomor' => 'PP/001/2026']);

    $this->actingAs($this->karyawan)->post('/pengajuan', isian())->assertSessionHasErrors('nomor');

    $lama->delete();
    $this->actingAs($this->karyawan)->post('/pengajuan', isian())->assertSessionHasNoErrors();
});

it('menolak relasi ke dokumen yang tidak terlihat atau ke dirinya sendiri', function () {
    $rahasiaDivisiLain = Document::factory()->disetujui()->create();
    $belumDisetujui = Document::factory()->publik()->create(['division_id' => $this->divisi->id]);
    $boleh = Document::factory()->disetujui()->publik()->create();

    foreach ([$rahasiaDivisiLain, $belumDisetujui] as $target) {
        $this->actingAs($this->karyawan)
            ->post('/pengajuan', isian(['relasi' => [['jenis' => 'mencabut', 'document_id' => $target->id]]]))
            ->assertSessionHasErrors('relasi.0.document_id');
    }

    $this->actingAs($this->karyawan)
        ->post('/pengajuan', isian(['relasi' => [['jenis' => 'mencabut', 'document_id' => $boleh->id]]]))
        ->assertSessionHasNoErrors();
    $dok = Document::where('nomor', 'PP/001/2026')->sole();
    expect($dok->relasiKeluar->sole()->related_document_id)->toBe($boleh->id);

    $this->actingAs($this->karyawan)
        ->post("/pengajuan/{$dok->id}", isian(['_method' => 'put', 'berkas' => null, 'relasi' => [['jenis' => 'terkait', 'document_id' => $dok->id]]]))
        ->assertSessionHasErrors('relasi.0.document_id');
});

it('hanya pengaju yang bisa mengubah atau menghapus draf dan dokumen ditolak', function () {
    $dok = Document::factory()->create(['division_id' => $this->divisi->id, 'submitted_by' => $this->karyawan->id, 'nomor' => 'PP/001/2026']);
    $rekan = User::factory()->karyawan($this->divisi)->create();

    $this->actingAs($rekan)->get("/pengajuan/{$dok->id}/ubah")->assertForbidden();
    $this->actingAs($rekan)->post("/pengajuan/{$dok->id}", isian(['_method' => 'put']))->assertForbidden();
    $this->actingAs($rekan)->delete("/pengajuan/{$dok->id}")->assertForbidden();

    $this->actingAs($this->karyawan)
        ->post("/pengajuan/{$dok->id}", isian(['_method' => 'put', 'berkas' => null, 'judul' => 'Judul baru']))
        ->assertRedirect('/pengajuan');
    expect($dok->refresh()->judul)->toBe('Judul baru')->and($dok->berkas_path)->toBe('dokumen/contoh.pdf');

    $this->actingAs($this->karyawan)->delete("/pengajuan/{$dok->id}")->assertRedirect('/pengajuan');
    expect(Document::count())->toBe(0);
});

it('mengganti berkas lama saat berkas baru diunggah', function () {
    $this->actingAs($this->karyawan)->post('/pengajuan', isian());
    $dok = Document::sole();
    $lama = $dok->berkas_path;

    $this->actingAs($this->karyawan)->post("/pengajuan/{$dok->id}", isian(['_method' => 'put', 'berkas' => pdfAsli('baru.pdf')]));

    Storage::disk('local')->assertMissing($lama);
    Storage::disk('local')->assertExists($dok->refresh()->berkas_path);
    expect($dok->berkas_nama)->toBe('baru.pdf');
});

it('tidak bisa mengubah dokumen yang sedang menunggu atau sudah disetujui', function (StatusDokumen $status) {
    $dok = Document::factory()->status($status)->create(['division_id' => $this->divisi->id, 'submitted_by' => $this->karyawan->id]);

    $this->actingAs($this->karyawan)->post("/pengajuan/{$dok->id}", isian(['_method' => 'put', 'berkas' => null]))->assertForbidden();
    $this->actingAs($this->karyawan)->delete("/pengajuan/{$dok->id}")->assertForbidden();
})->with([StatusDokumen::MenungguManajer, StatusDokumen::MenungguGm, StatusDokumen::Disetujui]);

it('menampilkan catatan penolakan pada pengajuan yang ditolak', function () {
    $dok = Document::factory()->status(StatusDokumen::Ditolak)->create(['division_id' => $this->divisi->id, 'submitted_by' => $this->karyawan->id]);
    $dok->approvalLogs()->create(['user_id' => $this->manajer->id, 'aksi' => 'tolak', 'tahap' => 'manajer', 'catatan' => 'Lampiran belum lengkap.']);

    $this->actingAs($this->karyawan)->get('/pengajuan')->assertInertia(fn (AssertableInertia $page) => $page
        ->component('pengajuan/index')
        ->where('dokumen.data.0.catatan_penolakan', 'Lampiran belum lengkap.'));
});

it('daftar pengajuan hanya memuat milik sendiri', function () {
    Document::factory()->create(['division_id' => $this->divisi->id]);
    Document::factory()->create(['division_id' => $this->divisi->id, 'submitted_by' => $this->karyawan->id]);

    $this->actingAs($this->karyawan)->get('/pengajuan')->assertInertia(fn (AssertableInertia $page) => $page->has('dokumen.data', 1));
});

it('mengajukan draf lewat tombol ajukan dan menampilkan pesan bila divisi tanpa manajer', function () {
    $dok = Document::factory()->create(['division_id' => $this->divisi->id, 'submitted_by' => $this->karyawan->id]);
    $this->actingAs($this->karyawan)->post("/pengajuan/{$dok->id}/ajukan")->assertRedirect()->assertSessionHas('sukses');
    expect($dok->refresh()->status)->toBe(StatusDokumen::MenungguManajer);

    $sendiri = User::factory()->karyawan()->create();
    $dok2 = Document::factory()->create(['division_id' => $sendiri->division_id, 'submitted_by' => $sendiri->id]);
    $this->actingAs($sendiri)->from('/pengajuan')->post("/pengajuan/{$dok2->id}/ajukan")
        ->assertRedirect('/pengajuan')->assertSessionHas('galat', 'Divisi belum memiliki manajer. Hubungi Admin.');
});

it('admin dan gm tidak bisa membuka halaman pengajuan', function (string $peran) {
    $this->actingAs(User::factory()->{$peran}()->create())->get('/pengajuan')->assertForbidden();
    $this->actingAs(User::factory()->{$peran}()->create())->post('/pengajuan', isian())->assertForbidden();
})->with(['admin', 'gm']);

it('guest diarahkan ke login', function () {
    $this->get('/pengajuan')->assertRedirect('/login');
});
