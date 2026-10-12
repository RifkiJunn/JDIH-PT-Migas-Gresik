<?php

namespace Database\Seeders;

use App\Enums\Akses;
use App\Enums\JenisRelasi;
use App\Enums\Peran;
use App\Enums\StatusDokumen;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $divisi = collect(['Legal', 'HSE', 'Operasional', 'SDM', 'Umum'])
            ->mapWithKeys(fn ($nama) => [$nama => Division::create(['nama' => $nama])]);

        $jenis = collect([
            'Peraturan Perusahaan' => false,
            'Keputusan Direksi' => false,
            'SOP' => false,
            'Kontrak' => true,
            'Perizinan' => true,
        ])->mapWithKeys(fn ($perlu, $nama) => [$nama => DocumentType::create(['nama' => $nama, 'perlu_kadaluarsa' => $perlu])]);

        // Semua akun contoh memakai password "password".
        $akun = fn (string $nama, string $email, Peran $peran, ?Division $d = null) => User::create([
            'name' => $nama, 'email' => $email, 'password' => 'password', 'peran' => $peran, 'division_id' => $d?->id,
        ]);

        $akun('Admin Sistem', 'admin@ptgm.test', Peran::Admin);
        $akun('General Manager', 'gm@ptgm.test', Peran::Gm);
        $manajerLegal = $akun('Manajer Legal', 'manajer.legal@ptgm.test', Peran::Manajer, $divisi['Legal']);
        $karyawanLegal = $akun('Karyawan Legal', 'karyawan.legal@ptgm.test', Peran::Karyawan, $divisi['Legal']);
        $akun('Manajer HSE', 'manajer.hse@ptgm.test', Peran::Manajer, $divisi['HSE']);
        $karyawanHse = $akun('Karyawan HSE', 'karyawan.hse@ptgm.test', Peran::Karyawan, $divisi['HSE']);

        $buat = function (array $data, User $pengaju) {
            $path = 'dokumen/contoh-'.str($data['nomor'])->slug().'.pdf';
            Storage::disk('local')->put($path, $pdf = self::pdfContoh($data['judul']));

            return Document::create($data + [
                'division_id' => $pengaju->division_id,
                'submitted_by' => $pengaju->id,
                'status' => StatusDokumen::Disetujui,
                'disetujui_pada' => now(),
                'berkas_path' => $path,
                'berkas_nama' => str($data['nomor'])->slug().'.pdf',
                'berkas_ukuran' => strlen($pdf),
            ]);
        };

        $pp2021 = $buat([
            'judul' => 'Peraturan Perusahaan tentang Tata Kelola Perusahaan yang Baik',
            'nomor' => 'PP/001/2021', 'document_type_id' => $jenis['Peraturan Perusahaan']->id, 'akses' => Akses::Publik,
            'tanggal_penetapan' => '2021-02-15', 'subjek' => 'Tata Kelola',
            'abstrak' => 'Mengatur prinsip transparansi, akuntabilitas, tanggung jawab, kemandirian, dan kewajaran dalam pengelolaan perusahaan.',
        ], $karyawanLegal);

        $pp2024 = $buat([
            'judul' => 'Peraturan Perusahaan tentang Perubahan atas Pedoman Tata Kelola Perusahaan yang Baik',
            'nomor' => 'PP/004/2024', 'document_type_id' => $jenis['Peraturan Perusahaan']->id, 'akses' => Akses::Publik,
            'tanggal_penetapan' => '2024-06-03', 'subjek' => 'Tata Kelola',
            'abstrak' => 'Menyesuaikan ketentuan komite audit dan pelaporan benturan kepentingan dengan peraturan daerah terbaru.',
        ], $karyawanLegal);
        $pp2024->relasiKeluar()->create(['related_document_id' => $pp2021->id, 'jenis' => JenisRelasi::Mengubah]);

        $buat([
            'judul' => 'Keputusan Direksi tentang Kebijakan Keselamatan, Kesehatan Kerja, dan Lindungan Lingkungan',
            'nomor' => 'SK-DIR/012/2023', 'document_type_id' => $jenis['Keputusan Direksi']->id, 'akses' => Akses::Publik,
            'tanggal_penetapan' => '2023-08-09', 'subjek' => 'K3LL',
            'abstrak' => 'Menetapkan komitmen perusahaan terhadap keselamatan pekerja, mitra kerja, dan lingkungan di seluruh wilayah operasi.',
        ], $karyawanHse);

        $buat([
            'judul' => 'Perjanjian Jual Beli Gas Bumi dengan PT Sarana Distribusi Wilayah Jawa Timur',
            'nomor' => 'PKS/LGL/014/2025', 'document_type_id' => $jenis['Kontrak']->id, 'akses' => Akses::Divisi,
            'tanggal_penetapan' => '2025-02-14', 'tanggal_kadaluarsa' => now()->addDays(45)->toDateString(), 'subjek' => 'Niaga Gas',
            'abstrak' => 'Perjanjian jual beli gas bumi jangka panjang dengan skema harga tetap dan eskalasi tahunan.',
        ], $manajerLegal);

        $buat([
            'judul' => 'SOP Pengoperasian Stasiun Regulator Tekanan Gas',
            'nomor' => 'SOP-HSE/012/2022', 'document_type_id' => $jenis['SOP']->id, 'akses' => Akses::Divisi,
            'tanggal_penetapan' => '2022-01-20', 'subjek' => 'K3LL',
            'abstrak' => 'Prosedur keselamatan bagi teknisi lapangan saat memelihara dan mengoperasikan stasiun regulator tekanan gas.',
        ], $karyawanHse);
    }

    /** PDF satu halaman yang sah, cukup untuk mencoba pratinjau dan unduhan. */
    private static function pdfContoh(string $judul): string
    {
        $teks = preg_replace('/[^\x20-\x7E]/', '', str_replace(['\\', '(', ')'], '', $judul));
        $isi = "BT /F1 12 Tf 60 760 Td (Dokumen contoh) Tj 0 -22 Td (".substr($teks, 0, 80).") Tj ET";
        $objek = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($isi)." >>\nstream\n{$isi}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offset = [];
        foreach ($objek as $i => $o) {
            $offset[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$o}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach ($offset as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
