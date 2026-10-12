<?php

use App\Enums\Akses;
use App\Enums\StatusDokumen;
use App\Models\Division;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Tabel bagian 4 spec. Tiap baris: [status, akses, siapa, boleh lihat].
 * "siapa" diterjemahkan relatif terhadap divisi dokumen oleh aktor().
 */
dataset('visibilitas', function () {
    $s = StatusDokumen::class;
    $baris = [];

    $tabel = [
        // status,            akses,    guest, karyawan, karyawanLain, manajer, manajerLain, gm,    admin, pengaju
        ['disetujui',        'publik', true,  true,     true,         true,    true,        true,  true,  true],
        ['disetujui',        'divisi', false, true,     false,        true,    false,       true,  true,  true],
        ['draf',             'publik', false, false,    false,        false,   false,       false, false, true],
        ['ditolak',          'divisi', false, false,    false,        false,   false,       false, false, true],
        ['menunggu_manajer', 'publik', false, false,    false,        true,    false,       false, false, true],
        ['menunggu_manajer', 'divisi', false, false,    false,        true,    false,       false, false, true],
        ['menunggu_gm',      'publik', false, false,    false,        true,    false,       true,  false, true],
        ['menunggu_gm',      'divisi', false, false,    false,        true,    false,       true,  false, true],
    ];
    $aktor = ['guest', 'karyawan', 'karyawanLain', 'manajer', 'manajerLain', 'gm', 'admin', 'pengaju'];

    foreach ($tabel as [$status, $akses, ...$harapan]) {
        foreach ($aktor as $i => $siapa) {
            $baris["{$status}/{$akses} oleh {$siapa}"] = [$s::from($status), Akses::from($akses), $siapa, $harapan[$i]];
        }
    }

    return $baris;
});

function aktor(string $siapa, Document $dok): ?User
{
    return match ($siapa) {
        'guest' => null,
        'karyawan' => User::factory()->karyawan($dok->division)->create(),
        'karyawanLain' => User::factory()->karyawan()->create(),
        'manajer' => User::factory()->manajer($dok->division)->create(),
        'manajerLain' => User::factory()->manajer()->create(),
        'gm' => User::factory()->gm()->create(),
        'admin' => User::factory()->admin()->create(),
        'pengaju' => $dok->pengaju,
    };
}

it('menerapkan tabel visibilitas pada policy dan scope', function (StatusDokumen $status, Akses $akses, string $siapa, bool $boleh) {
    $dok = Document::factory()->status($status)->create(['akses' => $akses]);
    $user = aktor($siapa, $dok);

    expect(Gate::forUser($user)->allows('view', $dok))->toBe($boleh)
        ->and(Document::visibleTo($user)->whereKey($dok->id)->exists())->toBe($boleh);
})->with('visibilitas');

it('menolak guest untuk dokumen publik yang belum disetujui GM', function () {
    $dok = Document::factory()->publik()->status(StatusDokumen::MenungguGm)->create();

    expect(Gate::forUser(null)->allows('view', $dok))->toBeFalse()
        ->and(Gate::forUser(null)->allows('download', $dok))->toBeFalse();
});

it('mengizinkan admin melihat metadata dokumen divisi tetapi menolak unduhan', function () {
    $dok = Document::factory()->disetujui()->create();
    $admin = User::factory()->admin()->create();

    expect($admin->can('view', $dok))->toBeTrue()
        ->and($admin->can('download', $dok))->toBeFalse()
        ->and($admin->can('download', Document::factory()->disetujui()->publik()->create()))->toBeTrue();
});

it('mengikuti divisi dokumen, bukan divisi pengaju yang sudah pindah', function () {
    $dok = Document::factory()->disetujui()->create();
    $rekanLama = User::factory()->karyawan($dok->division)->create();
    $pindah = Division::factory()->create();
    $dok->pengaju->update(['division_id' => $pindah->id]);

    expect($rekanLama->can('view', $dok))->toBeTrue()
        ->and(User::factory()->karyawan($pindah)->create()->can('view', $dok))->toBeFalse();
});

it('menyamakan hak unduh dengan hak lihat untuk selain admin', function () {
    $dok = Document::factory()->disetujui()->create();

    expect(User::factory()->karyawan($dok->division)->create()->can('download', $dok))->toBeTrue()
        ->and(User::factory()->karyawan()->create()->can('download', $dok))->toBeFalse();
});
