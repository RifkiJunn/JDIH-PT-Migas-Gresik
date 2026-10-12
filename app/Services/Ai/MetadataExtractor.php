<?php

namespace App\Services\Ai;

/** Membaca PDF dan mengusulkan isi form pengajuan. Ganti implementasinya untuk berpindah penyedia. */
interface MetadataExtractor
{
    /**
     * @param  list<string>  $namaJenis  Nama jenis dokumen yang boleh dipilih model.
     * @return array<string, mixed> Keluaran mentah model dengan kunci judul, nomor, jenis,
     *                              tanggal_penetapan, tanggal_kadaluarsa, abstrak. Belum dibersihkan.
     *
     * @throws EkstraksiGagal
     */
    public function extract(string $absolutePdfPath, array $namaJenis): array;
}
