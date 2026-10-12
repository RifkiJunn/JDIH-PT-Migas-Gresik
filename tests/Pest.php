<?php

use Illuminate\Http\UploadedFile;

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/**
 * Unggahan dengan isi nyata. UploadedFile::fake() menebak MIME dari nama berkas,
 * jadi tidak bisa membedakan PDF asli dari berkas yang hanya diganti ekstensinya.
 */
function unggahan(string $isi, string $nama = 'dokumen.pdf'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'uji');
    file_put_contents($path, $isi);

    return new UploadedFile($path, $nama, null, null, true);
}

function pdfAsli(string $nama = 'dokumen.pdf'): UploadedFile
{
    return unggahan("%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n", $nama);
}
