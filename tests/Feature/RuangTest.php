<?php

use App\Enums\AksiAkses;
use App\Enums\StatusDokumen;
use App\Models\AccessLog;
use App\Models\Division;
use App\Models\Document;
use App\Models\User;

/** Nilai kartu statistik per kunci, mis. ['arsip' => 2, 'draf' => 1]. */
function statistik(string $url, ?User $user = null): array
{
    $tes = $user ? test()->actingAs($user) : test();

    return array_column($tes->get($url)->assertOk()->viewData('page')['props']['statistik'], 'nilai', 'kunci');
}

beforeEach(function () {
    $this->divisi = Division::factory()->create();
    $this->milik = fn (User $user, StatusDokumen $status) => Document::factory()->status($status)
        ->create(['division_id' => $this->divisi->id, 'submitted_by' => $user->id]);
});

it('karyawan melihat hitungan arsip yang boleh dilihatnya dan pengajuannya sendiri', function () {
    $karyawan = User::factory()->karyawan($this->divisi)->create();
    Document::factory()->disetujui()->publik()->create();
    Document::factory()->disetujui()->create(['division_id' => $this->divisi->id]);
    Document::factory()->disetujui()->create(); // divisi lain, tidak terhitung
    ($this->milik)($karyawan, StatusDokumen::Draf);
    ($this->milik)($karyawan, StatusDokumen::MenungguManajer);
    ($this->milik)($karyawan, StatusDokumen::MenungguGm);
    ($this->milik)($karyawan, StatusDokumen::Ditolak);
    ($this->milik)(User::factory()->karyawan($this->divisi)->create(), StatusDokumen::Draf); // draf orang lain

    expect(statistik('/ruang', $karyawan))->toBe(['arsip' => 2, 'draf' => 1, 'proses' => 2, 'ditolak' => 1]);
});

it('antrean manajer dan gm sama dengan halaman persetujuan', function () {
    $manajer = User::factory()->manajer($this->divisi)->create();
    $gm = User::factory()->gm()->create();
    ($this->milik)(User::factory()->karyawan($this->divisi)->create(), StatusDokumen::MenungguManajer);
    ($this->milik)($manajer, StatusDokumen::MenungguManajer); // pengajuan sendiri, bukan antreannya
    Document::factory()->status(StatusDokumen::MenungguManajer)->create(); // divisi lain
    Document::factory()->count(2)->status(StatusDokumen::MenungguGm)->create();

    expect(statistik('/ruang', $manajer))->toMatchArray(['antrean' => 1, 'proses' => 1])
        ->and(statistik('/ruang', $gm))->toMatchArray(['antrean' => 2]);
});

it('menghitung dokumen yang segera berakhir dan yang disetujui bulan ini', function () {
    Document::factory()->disetujui()->create(['tanggal_kadaluarsa' => today()->addDays(30), 'disetujui_pada' => now()]);
    Document::factory()->disetujui()->create(['tanggal_kadaluarsa' => today()->addYear(), 'disetujui_pada' => now()->subMonths(2)]);

    expect(statistik('/ruang', User::factory()->gm()->create()))->toMatchArray(['arsip' => 2, 'disetujui' => 1, 'segera' => 1]);
});

it('admin melihat hitungan data induk tanpa daftar dokumen', function () {
    $admin = User::factory()->admin()->create();
    $dok = Document::factory()->disetujui()->publik()->create();
    AccessLog::create(['document_id' => $dok->id, 'aksi' => AksiAkses::Lihat, 'ip' => '127.0.0.1']);

    expect(statistik('/ruang', $admin))->toMatchArray(['pengguna' => User::count(), 'divisi' => Division::count(), 'akses' => 1]);
    $this->actingAs($admin)->get('/ruang')->assertInertia(fn ($page) => $page->component('ruang')->where('terbaru', null));
});

it('ringkasan ruang internal menuntut login', function () {
    $this->get('/ruang')->assertRedirect('/login');
});

it('statistik beranda hanya menghitung dokumen publik yang disetujui', function () {
    $a = Document::factory()->disetujui()->publik()->create(['tanggal_penetapan' => '2024-03-01']);
    Document::factory()->disetujui()->publik()->create(['tanggal_penetapan' => '2021-03-01', 'document_type_id' => $a->document_type_id]);
    $divisi = Document::factory()->disetujui()->create(['tanggal_penetapan' => '2025-03-01']);
    foreach ([$a, $a, $divisi] as $dok) {
        AccessLog::create(['document_id' => $dok->id, 'aksi' => AksiAkses::Unduh, 'ip' => '127.0.0.1']);
    }
    AccessLog::create(['document_id' => $a->id, 'aksi' => AksiAkses::Lihat, 'ip' => '127.0.0.1']);

    expect(statistik('/'))->toBe(['arsip' => 2, 'jenis' => 1, 'tahun' => 1, 'unduh' => 2]);
});
