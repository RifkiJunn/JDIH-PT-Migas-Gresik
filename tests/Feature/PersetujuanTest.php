<?php

use App\Enums\StatusDokumen;
use App\Models\Division;
use App\Models\Document;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->divisi = Division::factory()->create();
    $this->manajer = User::factory()->manajer($this->divisi)->create();
    $this->gm = User::factory()->gm()->create();
});

function menunggu(StatusDokumen $status, ?Division $divisi = null): Document
{
    return Document::factory()->status($status)->create($divisi ? ['division_id' => $divisi->id] : []);
}

it('manajer hanya melihat antrean menunggu_manajer divisinya', function () {
    $milik = menunggu(StatusDokumen::MenungguManajer, $this->divisi);
    menunggu(StatusDokumen::MenungguManajer);
    menunggu(StatusDokumen::MenungguGm, $this->divisi);
    menunggu(StatusDokumen::Draf, $this->divisi);

    $this->actingAs($this->manajer)->get('/persetujuan')->assertInertia(fn (AssertableInertia $page) => $page
        ->component('persetujuan/index')->has('dokumen', 1)->where('dokumen.0.id', $milik->id));
});

it('gm melihat antrean menunggu_gm semua divisi', function () {
    menunggu(StatusDokumen::MenungguGm, $this->divisi);
    menunggu(StatusDokumen::MenungguGm);
    menunggu(StatusDokumen::MenungguManajer);

    $this->actingAs($this->gm)->get('/persetujuan')->assertInertia(fn (AssertableInertia $page) => $page->has('dokumen', 2));
});

it('menyetujui lewat http memindahkan status', function () {
    $dok = menunggu(StatusDokumen::MenungguManajer, $this->divisi);

    $this->actingAs($this->manajer)->post("/persetujuan/{$dok->id}/setujui")->assertRedirect('/persetujuan')->assertSessionHas('sukses');
    expect($dok->refresh()->status)->toBe(StatusDokumen::MenungguGm);

    $this->actingAs($this->gm)->post("/persetujuan/{$dok->id}/setujui")->assertRedirect('/persetujuan');
    expect($dok->refresh()->status)->toBe(StatusDokumen::Disetujui);
});

it('menolak tanpa catatan mengembalikan galat validasi', function () {
    $dok = menunggu(StatusDokumen::MenungguManajer, $this->divisi);

    $this->actingAs($this->manajer)->post("/persetujuan/{$dok->id}/tolak", ['catatan' => ''])->assertSessionHasErrors('catatan');
    expect($dok->refresh()->status)->toBe(StatusDokumen::MenungguManajer);

    $this->actingAs($this->manajer)->post("/persetujuan/{$dok->id}/tolak", ['catatan' => 'Nomor keliru.'])->assertRedirect('/persetujuan');
    expect($dok->refresh()->status)->toBe(StatusDokumen::Ditolak);
});

it('TransisiTidakSah ditampilkan sebagai pesan, bukan halaman galat', function () {
    $dok = menunggu(StatusDokumen::MenungguGm, $this->divisi);

    // Manajer mengirim keputusan dari tab lama: dokumen sudah naik ke GM.
    $this->actingAs($this->manajer)->from("/persetujuan/{$dok->id}")->post("/persetujuan/{$dok->id}/setujui")
        ->assertRedirect("/persetujuan/{$dok->id}")
        ->assertSessionHas('galat', 'Dokumen ini sudah diputuskan atau bukan wewenang Anda.');
    expect($dok->refresh()->status)->toBe(StatusDokumen::MenungguGm);
});

it('halaman tinjau menolak dokumen di luar wewenang', function () {
    $this->actingAs($this->manajer)->get('/persetujuan/'.menunggu(StatusDokumen::MenungguManajer)->id)->assertForbidden();
    $this->actingAs($this->manajer)->get('/persetujuan/'.menunggu(StatusDokumen::MenungguManajer, $this->divisi)->id)
        ->assertInertia(fn (AssertableInertia $page) => $page->component('persetujuan/show')->where('bolehMemutuskan', true));
});

it('karyawan dan admin tidak bisa membuka persetujuan', function (string $peran) {
    $this->actingAs(User::factory()->{$peran}()->create())->get('/persetujuan')->assertForbidden();
})->with(['karyawan', 'admin']);
