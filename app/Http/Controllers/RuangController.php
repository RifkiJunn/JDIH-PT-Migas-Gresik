<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Enums\StatusDokumen;
use App\Models\AccessLog;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Ringkasan ruang internal: kartu statistik menurut peran, lalu dokumen terbaru di arsip. */
class RuangController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $admin = $user->peran === Peran::Admin;
        $arsip = fn () => Document::visibleTo($user)->disetujui();

        return Inertia::render('ruang', [
            'statistik' => $this->statistik($user),
            // Admin tidak ikut alur dokumen dan tidak punya menu arsip.
            'terbaru' => $admin ? null : $arsip()->with('type', 'division', 'pengaju')
                ->orderByDesc('disetujui_pada')->orderByDesc('id')->limit(5)->get()
                ->map(fn (Document $d) => $d->ringkas()),
        ]);
    }

    /** @return list<array{kunci: string, label: string, nilai: int, keterangan: string, href: string, sorot?: bool}> */
    private function statistik(User $user): array
    {
        $divisi = $user->division?->nama;
        $milik = fn (StatusDokumen ...$status) => Document::where('submitted_by', $user->id)->whereIn('status', $status)->count();
        $kartu = fn (string $kunci, string $label, int $nilai, string $keterangan, string $href, bool $sorot = false) => compact('kunci', 'label', 'nilai', 'keterangan', 'href', 'sorot');

        $arsip = fn (string $keterangan) => $kartu('arsip', 'Dokumen arsip', Document::visibleTo($user)->disetujui()->count(), $keterangan, '/arsip');
        $antrean = fn (string $keterangan) => $kartu('antrean', 'Menunggu persetujuan', $n = Document::antrean($user)->count(), $keterangan, '/persetujuan', $n > 0);
        $proses = fn () => $kartu('proses', 'Sedang diproses', $milik(StatusDokumen::MenungguManajer, StatusDokumen::MenungguGm), 'Pengajuan saya yang belum diputuskan', '/pengajuan');
        $segera = fn () => $kartu('segera', 'Segera berakhir', Document::visibleTo($user)->disetujui()->saring(['kadaluarsa' => '1'])->count(), 'Masa berlaku habis dalam 90 hari', '/arsip?kadaluarsa=1');

        return match ($user->peran) {
            Peran::Karyawan => [
                $arsip("Publik dan divisi {$divisi}"),
                $kartu('draf', 'Draf', $milik(StatusDokumen::Draf), 'Belum diajukan', '/pengajuan'),
                $proses(),
                $kartu('ditolak', 'Perlu diperbaiki', $n = $milik(StatusDokumen::Ditolak), 'Ditolak dengan catatan', '/pengajuan', $n > 0),
            ],
            Peran::Manajer => [
                $antrean("Pengajuan divisi {$divisi}"),
                $arsip("Publik dan divisi {$divisi}"),
                $proses(),
                $segera(),
            ],
            Peran::Gm => [
                $antrean('Persetujuan akhir, semua divisi'),
                $arsip('Semua divisi'),
                $kartu('disetujui', 'Disetujui bulan ini', Document::disetujui()->where('disetujui_pada', '>=', now()->startOfMonth())->count(), now()->translatedFormat('F Y'), '/arsip'),
                $segera(),
            ],
            Peran::Admin => [
                $kartu('pengguna', 'Pengguna', User::count(), 'Akun terdaftar', '/admin/pengguna'),
                $kartu('divisi', 'Divisi', Division::count(), 'Unit pemilik dokumen', '/admin/divisi'),
                $kartu('jenis', 'Jenis dokumen', DocumentType::count(), 'Klasifikasi dokumen', '/admin/jenis-dokumen'),
                $kartu('akses', 'Akses dokumen', AccessLog::where('created_at', '>=', now()->subDays(30))->count(), 'Dilihat dan diunduh, 30 hari terakhir', '/admin/log'),
            ],
        };
    }
}
