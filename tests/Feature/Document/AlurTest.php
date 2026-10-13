<?php

use App\Enums\AksiPersetujuan;
use App\Enums\StatusDokumen;
use App\Enums\Tahap;
use App\Exceptions\TransisiTidakSah;
use App\Models\Division;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentWorkflow;

beforeEach(function () {
    $this->alur = app(DocumentWorkflow::class);
    $this->divisi = Division::factory()->create();
    $this->karyawan = User::factory()->karyawan($this->divisi)->create();
    $this->manajer = User::factory()->manajer($this->divisi)->create();
    $this->gm = User::factory()->gm()->create();
});

function dokMilik(User $pengaju, StatusDokumen $status = StatusDokumen::Draf): Document
{
    return Document::factory()->status($status)->create(['division_id' => $pengaju->division_id, 'submitted_by' => $pengaju->id]);
}

it('karyawan mengajukan: draf -> menunggu_manajer, log ajukan', function () {
    $dok = dokMilik($this->karyawan);

    $this->alur->ajukan($dok, $this->karyawan);

    expect($dok->refresh()->status)->toBe(StatusDokumen::MenungguManajer);
    $log = $dok->approvalLogs->sole();
    expect($log->aksi)->toBe(AksiPersetujuan::Ajukan)->and($log->tahap)->toBe(Tahap::Manajer)->and($log->user_id)->toBe($this->karyawan->id);
});

it('manajer mengajukan: draf -> menunggu_gm', function () {
    $dok = dokMilik($this->manajer);

    $this->alur->ajukan($dok, $this->manajer);

    expect($dok->refresh()->status)->toBe(StatusDokumen::MenungguGm);
});

it('manajer menyetujui: menunggu_manajer -> menunggu_gm', function () {
    $dok = dokMilik($this->karyawan, StatusDokumen::MenungguManajer);

    $this->alur->setujui($dok, $this->manajer);

    expect($dok->refresh()->status)->toBe(StatusDokumen::MenungguGm)->and($dok->disetujui_pada)->toBeNull();
});

it('gm menyetujui: menunggu_gm -> disetujui, disetujui_pada terisi', function () {
    $dok = dokMilik($this->karyawan, StatusDokumen::MenungguGm);

    $this->alur->setujui($dok, $this->gm);

    expect($dok->refresh()->status)->toBe(StatusDokumen::Disetujui)->and($dok->disetujui_pada)->not->toBeNull();
    expect($dok->approvalLogs->sole()->tahap)->toBe(Tahap::Gm);
});

it('penolakan di tiap tahap -> ditolak dan mencatat catatan', function (StatusDokumen $status, string $oleh) {
    $dok = dokMilik($this->karyawan, $status);

    $this->alur->tolak($dok, $this->{$oleh}, 'Nomor dokumen keliru.');

    expect($dok->refresh()->status)->toBe(StatusDokumen::Ditolak);
    $log = $dok->approvalLogs->sole();
    expect($log->aksi)->toBe(AksiPersetujuan::Tolak)->and($log->catatan)->toBe('Nomor dokumen keliru.');
})->with([[StatusDokumen::MenungguManajer, 'manajer'], [StatusDokumen::MenungguGm, 'gm']]);

it('menolak tanpa catatan gagal', function () {
    $dok = dokMilik($this->karyawan, StatusDokumen::MenungguManajer);

    expect(fn () => $this->alur->tolak($dok, $this->manajer, '   '))->toThrow(TransisiTidakSah::class);
    expect($dok->refresh()->status)->toBe(StatusDokumen::MenungguManajer);
});

it('ditolak lalu diajukan lagi kembali ke menunggu_manajer', function () {
    $dok = dokMilik($this->karyawan, StatusDokumen::Ditolak);

    $this->alur->ajukan($dok, $this->karyawan);

    expect($dok->refresh()->status)->toBe(StatusDokumen::MenungguManajer);
});

it('manajer divisi lain tidak boleh memutuskan', function () {
    $dok = dokMilik($this->karyawan, StatusDokumen::MenungguManajer);
    $lain = User::factory()->manajer()->create();

    expect($lain->can('decide', $dok))->toBeFalse();
    expect(fn () => $this->alur->setujui($dok, $lain))->toThrow(TransisiTidakSah::class);
});

it('manajer tidak boleh memutuskan pengajuannya sendiri', function () {
    $dok = dokMilik($this->manajer, StatusDokumen::MenungguManajer);

    expect($this->manajer->can('decide', $dok))->toBeFalse();
});

it('karyawan dan admin tidak boleh memutuskan', function () {
    $dok = dokMilik($this->karyawan, StatusDokumen::MenungguGm);

    expect($this->karyawan->can('decide', $dok))->toBeFalse()
        ->and(User::factory()->admin()->create()->can('decide', $dok))->toBeFalse();
});

it('keputusan atas dokumen yang sudah diputuskan melempar TransisiTidakSah', function () {
    $dok = dokMilik($this->karyawan, StatusDokumen::MenungguManajer);
    $salinanTabLama = Document::find($dok->id);
    $this->alur->setujui($dok, $this->manajer);

    expect(fn () => $this->alur->setujui($salinanTabLama, $this->manajer))->toThrow(TransisiTidakSah::class);
    expect(fn () => $this->alur->tolak($salinanTabLama, $this->manajer, 'terlambat'))->toThrow(TransisiTidakSah::class);
    expect($dok->refresh()->status)->toBe(StatusDokumen::MenungguGm)->and($dok->approvalLogs)->toHaveCount(1);
});

it('pengajuan karyawan di divisi tanpa manajer melempar TransisiTidakSah', function () {
    $sendiri = User::factory()->karyawan()->create();
    $dok = dokMilik($sendiri);

    expect(fn () => $this->alur->ajukan($dok, $sendiri))
        ->toThrow(TransisiTidakSah::class, 'Divisi belum memiliki manajer. Hubungi Admin.');
    expect($dok->refresh()->status)->toBe(StatusDokumen::Draf);
});

it('hanya pengaju yang boleh mengajukan', function () {
    $dok = dokMilik($this->karyawan);

    expect(fn () => $this->alur->ajukan($dok, User::factory()->karyawan($this->divisi)->create()))->toThrow(TransisiTidakSah::class);
});

it('dokumen disetujui tidak bisa diubah atau dihapus', function () {
    $dok = dokMilik($this->karyawan, StatusDokumen::Disetujui);

    expect($this->karyawan->can('update', $dok))->toBeFalse()->and($this->karyawan->can('delete', $dok))->toBeFalse();
    expect(fn () => $this->alur->ajukan($dok, $this->karyawan))->toThrow(TransisiTidakSah::class);
});

it('pengaju boleh mengubah dan menghapus draf serta dokumen ditolak, orang lain tidak', function (StatusDokumen $status) {
    $dok = dokMilik($this->karyawan, $status);

    expect($this->karyawan->can('update', $dok))->toBeTrue()->and($this->karyawan->can('delete', $dok))->toBeTrue()
        ->and($this->manajer->can('update', $dok))->toBeFalse();
})->with([StatusDokumen::Draf, StatusDokumen::Ditolak]);

it('mengarahkan tiap peran ke ringkasan ruang internal setelah login', function (string $peran) {
    $user = User::factory()->{$peran}()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/ruang');
})->with(['karyawan', 'manajer', 'gm', 'admin']);

it('middleware peran menolak peran yang tidak cocok', function () {
    Route::middleware(['web', 'auth', 'peran:gm,admin'])->get('/_uji-peran', fn () => 'ok');

    $this->actingAs($this->karyawan)->get('/_uji-peran')->assertForbidden();
    $this->actingAs($this->gm)->get('/_uji-peran')->assertOk();
});
