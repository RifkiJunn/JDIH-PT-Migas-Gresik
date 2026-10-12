<?php

namespace App\Http\Controllers;

use App\Enums\AksiAkses;
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

    /** Beranda JDIH: selalu versi publik, juga bagi pengunjung yang sedang login. */
    public function beranda(Request $request): Response
    {
        $publik = fn () => Document::visibleTo(null)->disetujui();

        return Inertia::render('beranda', [
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
        $document->hasMany(\App\Models\AccessLog::class)->create([
            'user_id' => $request->user()?->id,
            'aksi' => $aksi,
            'ip' => $request->ip(),
        ]);
    }
}
