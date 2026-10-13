<?php

namespace App\Http\Controllers;

use App\Enums\AksiAkses;
use App\Models\AccessLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Portal publik, arsip internal, detail dokumen, dan berkasnya. */
class DokumenController extends Controller
{
    private const FILTER = ['q', 'jenis', 'tahun', 'subjek', 'kadaluarsa'];

    /**
     * Beranda JDIH: ringkasan koleksi publik. Daftar jenis dan jumlahnya datang dari
     * prop bersama `jenisPublik` (HandleInertiaRequests), yang juga mengisi menu dan kaki halaman.
     */
    public function beranda(): Response
    {
        $publik = fn () => Document::visibleTo(null)->disetujui();

        $total = $publik()->count();
        $tahun = Document::pilihanFilter($publik())['tahun'];
        $terkini = $tahun->first() ?? now()->year;
        $kartu = fn (string $kunci, string $label, int $nilai, string $keterangan, ?string $href = null) => compact('kunci', 'label', 'nilai', 'keterangan', 'href');

        return Inertia::render('beranda', [
            'total' => $total,
            'tahun' => $tahun,
            'statistik' => [
                $kartu('arsip', 'Dokumen', $total, 'Terbuka untuk umum', '/dokumen'),
                $kartu('jenis', 'Jenis dokumen', $publik()->distinct()->count('document_type_id'), 'Klasifikasi yang tersedia', '#menurut-jenis'),
                $kartu('tahun', "Ditetapkan {$terkini}", $publik()->whereYear('tanggal_penetapan', $terkini)->count(), 'Tahun penetapan terbaru', "/dokumen?tahun={$terkini}"),
                $kartu('unduh', 'Unduhan', AccessLog::where('aksi', AksiAkses::Unduh)
                    ->whereHas('document', fn ($q) => $q->visibleTo(null)->disetujui())->count(), 'Berkas publik diunduh'),
            ],
            'terbaru' => $publik()->with('type', 'division', 'pengaju')
                ->orderByDesc('tanggal_penetapan')->orderByDesc('id')->limit(5)->get()
                ->map(fn (Document $d) => $d->ringkas()),
        ]);
    }

    /** Daftar dokumen publik: selalu versi publik, juga bagi pengunjung yang sedang login. */
    public function index(Request $request): Response
    {
        $publik = fn () => Document::visibleTo(null)->disetujui();

        return Inertia::render('dokumen/index', [
            'dokumen' => $this->daftar($publik(), $request),
            'filter' => $request->only(self::FILTER),
            'pilihan' => Document::pilihanFilter($publik()),
        ]);
    }

    public function arsip(Request $request): Response
    {
        $terlihat = fn () => Document::visibleTo($request->user())->disetujui();

        return Inertia::render('arsip/index', [
            'dokumen' => $this->daftar($terlihat(), $request),
            'filter' => $request->only(self::FILTER),
            'pilihan' => Document::pilihanFilter($terlihat()),
        ]);
    }

    public function show(Request $request, Document $document): Response
    {
        Gate::forUser($request->user())->authorize('view', $document);
        $this->catat($request, $document, AksiAkses::Lihat);

        return Inertia::render('dokumen/show', ['dokumen' => $document->rinci($request->user())]);
    }

    public function lihatBerkas(Request $request, Document $document): StreamedResponse
    {
        return $this->berkas($request, $document, inline: true);
    }

    public function unduhBerkas(Request $request, Document $document): StreamedResponse
    {
        $respons = $this->berkas($request, $document, inline: false);
        $this->catat($request, $document, AksiAkses::Unduh);

        return $respons;
    }

    private function daftar($query, Request $request)
    {
        return $query->saring($request->only(self::FILTER))
            ->with('type', 'division', 'pengaju')
            ->orderByDesc('tanggal_penetapan')->orderByDesc('id')
            ->paginate(20)->withQueryString()
            ->through(fn (Document $d) => $d->ringkas());
    }

    private function berkas(Request $request, Document $document, bool $inline): StreamedResponse
    {
        Gate::forUser($request->user())->authorize('download', $document);
        abort_unless(Storage::disk('local')->exists($document->berkas_path), 404);

        return Storage::disk('local')->response($document->berkas_path, $document->berkas_nama, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ], $inline ? 'inline' : 'attachment');
    }

    private function catat(Request $request, Document $document, AksiAkses $aksi): void
    {
        $document->hasMany(AccessLog::class)->create([
            'user_id' => $request->user()?->id,
            'aksi' => $aksi,
            'ip' => $request->ip(),
        ]);
    }
}
