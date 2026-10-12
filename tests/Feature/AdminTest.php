<?php

use App\Enums\Peran;
use App\Models\AccessLog;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->divisi = Division::factory()->create();
});

function akunBaru(array $ubah = []): array
{
    return $ubah + ['name' => 'Budi Santoso', 'email' => 'budi@ptgm.test', 'password' => 'rahasia-123', 'peran' => 'karyawan', 'division_id' => test()->divisi->id];
}

it('selain admin mendapat 403 di setiap route admin', function (string $peran) {
    $user = User::factory()->{$peran}()->create();

    foreach (['/admin/pengguna', '/admin/divisi', '/admin/jenis-dokumen', '/admin/log'] as $url) {
        $this->actingAs($user)->get($url)->assertForbidden();
    }
    $this->actingAs($user)->post('/admin/pengguna', akunBaru())->assertForbidden();
    $this->actingAs($user)->post('/admin/divisi', ['nama' => 'Keuangan'])->assertForbidden();
    expect(User::where('email', 'budi@ptgm.test')->exists())->toBeFalse();
})->with(['karyawan', 'manajer', 'gm']);

it('admin membuat akun dengan peran dan divisi', function () {
    $this->actingAs($this->admin)->post('/admin/pengguna', akunBaru(['peran' => 'manajer']))->assertSessionHasNoErrors()->assertRedirect();

    $user = User::where('email', 'budi@ptgm.test')->sole();
    expect($user->peran)->toBe(Peran::Manajer)->and($user->division_id)->toBe($this->divisi->id)
        ->and(Hash::check('rahasia-123', $user->password))->toBeTrue();
});

it('gm dan admin disimpan tanpa divisi; karyawan dan manajer wajib punya divisi', function () {
    $this->actingAs($this->admin)->post('/admin/pengguna', akunBaru(['peran' => 'gm']))->assertSessionHasNoErrors();
    expect(User::where('email', 'budi@ptgm.test')->sole()->division_id)->toBeNull();

    $this->actingAs($this->admin)->post('/admin/pengguna', akunBaru(['email' => 'x@ptgm.test', 'division_id' => null]))
        ->assertSessionHasErrors('division_id');
    $this->actingAs($this->admin)->post('/admin/pengguna', akunBaru(['email' => 'y@ptgm.test', 'peran' => 'direktur']))
        ->assertSessionHasErrors('peran');
});

it('menolak email ganda dan password pendek', function () {
    $this->actingAs($this->admin)->post('/admin/pengguna', akunBaru(['email' => $this->admin->email]))->assertSessionHasErrors('email');
    $this->actingAs($this->admin)->post('/admin/pengguna', akunBaru(['password' => '123']))->assertSessionHasErrors('password');
});

it('admin mengubah akun dan mereset password; password kosong berarti tidak diubah', function () {
    $user = User::factory()->karyawan($this->divisi)->create();
    $lama = $user->password;
    $pindah = Division::factory()->create();

    $this->actingAs($this->admin)->put("/admin/pengguna/{$user->id}", akunBaru(['email' => $user->email, 'password' => '', 'division_id' => $pindah->id]))
        ->assertSessionHasNoErrors();
    expect($user->refresh()->division_id)->toBe($pindah->id)->and($user->password)->toBe($lama);

    $this->actingAs($this->admin)->put("/admin/pengguna/{$user->id}", akunBaru(['email' => $user->email, 'password' => 'password-baru']))
        ->assertSessionHasNoErrors();
    expect(Hash::check('password-baru', $user->refresh()->password))->toBeTrue();
});

it('admin tidak bisa menurunkan peran akunnya sendiri', function () {
    $this->actingAs($this->admin)
        ->put("/admin/pengguna/{$this->admin->id}", akunBaru(['email' => $this->admin->email, 'password' => '', 'peran' => 'karyawan']))
        ->assertSessionHasErrors('peran');
    expect($this->admin->refresh()->peran)->toBe(Peran::Admin);
});

it('mengelola divisi dan jenis dokumen', function () {
    $this->actingAs($this->admin)->post('/admin/divisi', ['nama' => 'Keuangan'])->assertSessionHasNoErrors();
    $this->actingAs($this->admin)->post('/admin/divisi', ['nama' => 'Keuangan'])->assertSessionHasErrors('nama');

    $this->actingAs($this->admin)->post('/admin/jenis-dokumen', ['nama' => 'Kontrak', 'perlu_kadaluarsa' => true])->assertSessionHasNoErrors();
    $jenis = DocumentType::where('nama', 'Kontrak')->sole();
    expect($jenis->perlu_kadaluarsa)->toBeTrue();

    $this->actingAs($this->admin)->put("/admin/jenis-dokumen/{$jenis->id}", ['nama' => 'Kontrak Kerja', 'perlu_kadaluarsa' => false])->assertSessionHasNoErrors();
    expect($jenis->refresh()->nama)->toBe('Kontrak Kerja')->and($jenis->perlu_kadaluarsa)->toBeFalse();
});

it('divisi atau jenis yang masih dipakai tidak bisa dihapus', function () {
    $dok = Document::factory()->create();
    $berakun = Division::factory()->create();
    User::factory()->karyawan($berakun)->create();

    $this->actingAs($this->admin)->delete("/admin/divisi/{$dok->division_id}")->assertSessionHas('galat');
    $this->actingAs($this->admin)->delete("/admin/divisi/{$berakun->id}")->assertSessionHas('galat');
    $this->actingAs($this->admin)->delete("/admin/jenis-dokumen/{$dok->document_type_id}")->assertSessionHas('galat');
    expect(Division::whereKey([$dok->division_id, $berakun->id])->count())->toBe(2)->and(DocumentType::whereKey($dok->document_type_id)->exists())->toBeTrue();

    $this->actingAs($this->admin)->delete("/admin/divisi/{$this->divisi->id}")->assertSessionHas('sukses');
    expect(Division::whereKey($this->divisi->id)->exists())->toBeFalse();
});

it('log memuat persetujuan dan akses, bisa disaring tanggal', function () {
    $dok = Document::factory()->disetujui()->create();
    $dok->approvalLogs()->create(['user_id' => $this->admin->id, 'aksi' => 'setuju', 'tahap' => 'gm']);
    AccessLog::create(['document_id' => $dok->id, 'aksi' => 'unduh', 'ip' => '10.0.0.1']);

    $this->actingAs($this->admin)->get('/admin/log')->assertInertia(fn (AssertableInertia $page) => $page
        ->component('admin/log')->where('tab', 'persetujuan')->has('log.data', 1)->where('log.data.0.dokumen', $dok->judul));
    $this->actingAs($this->admin)->get('/admin/log?tab=akses')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('log.data', 1)->where('log.data.0.oleh', 'Pengunjung'));
    $this->actingAs($this->admin)->get('/admin/log?tab=akses&dari='.today()->addDay()->toDateString())
        ->assertInertia(fn (AssertableInertia $page) => $page->has('log.data', 0));
    $this->actingAs($this->admin)->get('/admin/log?tab=ngawur&dari=bukan-tanggal')->assertOk();
});
