<?php

use App\Enums\JenisRelasi;
use App\Enums\StatusDokumen;
use App\Models\Document;

function relasikan(Document $sumber, Document $target, JenisRelasi $jenis): void
{
    $sumber->relasiKeluar()->create(['related_document_id' => $target->id, 'jenis' => $jenis]);
}

it('berakhir bila disetujui dan tanggal kadaluarsa lewat', function () {
    $dok = Document::factory()->disetujui()->create(['tanggal_kadaluarsa' => now()->subDay()]);

    expect($dok->isBerakhir())->toBeTrue();
});

it('tidak berakhir bila belum disetujui walau tanggal lewat', function () {
    $dok = Document::factory()->status(StatusDokumen::MenungguGm)->create(['tanggal_kadaluarsa' => now()->subDay()]);

    expect($dok->isBerakhir())->toBeFalse();
});

it('tidak berakhir pada hari kadaluarsa itu sendiri atau tanpa tanggal', function () {
    expect(Document::factory()->disetujui()->create(['tanggal_kadaluarsa' => today()])->isBerakhir())->toBeFalse()
        ->and(Document::factory()->disetujui()->create(['tanggal_kadaluarsa' => null])->isBerakhir())->toBeFalse();
});

it('berstatus Dicabut bila dokumen disetujui lain mencabutnya', function () {
    $lama = Document::factory()->disetujui()->create();
    relasikan(Document::factory()->disetujui()->create(), $lama, JenisRelasi::Mencabut);

    expect($lama->statusHukum())->toBe('Dicabut');
});

it('mengabaikan relasi dari dokumen yang belum disetujui dan relasi terkait', function () {
    $lama = Document::factory()->disetujui()->create();
    relasikan(Document::factory()->status(StatusDokumen::MenungguGm)->create(), $lama, JenisRelasi::Mencabut);
    relasikan(Document::factory()->disetujui()->create(), $lama, JenisRelasi::Terkait);

    expect($lama->statusHukum())->toBe('Berlaku');
});

it('mendahulukan Dicabut di atas Digantikan di atas Diubah', function () {
    $lama = Document::factory()->disetujui()->create();
    relasikan(Document::factory()->disetujui()->create(), $lama, JenisRelasi::Mengubah);
    expect($lama->statusHukum())->toBe('Diubah');

    relasikan(Document::factory()->disetujui()->create(), $lama, JenisRelasi::Menggantikan);
    expect($lama->refresh()->statusHukum())->toBe('Digantikan');

    relasikan(Document::factory()->disetujui()->create(), $lama, JenisRelasi::Mencabut);
    expect($lama->refresh()->statusHukum())->toBe('Dicabut');
});

it('mengambil tahun dari tanggal penetapan', function () {
    expect(Document::factory()->create(['tanggal_penetapan' => '2024-03-11'])->tahun())->toBe(2024);
});
