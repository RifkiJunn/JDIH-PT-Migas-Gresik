<?php

namespace App\Http\Controllers;

use App\Enums\AksiPersetujuan;
use App\Enums\StatusDokumen;
use App\Exceptions\TransisiTidakSah;
use App\Http\Requests\SimpanPengajuanRequest;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PengajuanController extends Controller
{
    public function index(Request $request): Response
    {
        $dokumen = Document::with('type', 'division', 'pengaju')
            ->where('submitted_by', $request->user()->id)
            ->latest('updated_at')
            ->paginate(20)
            ->through(fn (Document $d) => $d->ringkas() + [
                'catatan_penolakan' => $d->status === StatusDokumen::Ditolak
                    ? $d->approvalLogs()->where('aksi', AksiPersetujuan::Tolak)->latest('id')->value('catatan')
                    : null,
            ]);

        return Inertia::render('pengajuan/index', ['dokumen' => $dokumen]);
    }

    public function create(Request $request): Response
    {
        return $this->form($request, null);
    }

    public function edit(Request $request, Document $document): Response
    {
        Gate::authorize('update', $document);

        return $this->form($request, $document);
    }

    public function store(SimpanPengajuanRequest $request, DocumentWorkflow $alur): RedirectResponse
    {
        return $this->simpan($request, new Document, $alur);
    }

    public function update(SimpanPengajuanRequest $request, Document $document, DocumentWorkflow $alur): RedirectResponse
    {
        return $this->simpan($request, $document, $alur);
    }

    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);
        $document->delete();

        return to_route('pengajuan.index')->with('sukses', 'Pengajuan dihapus.');
    }

    public function ajukan(Request $request, Document $document, DocumentWorkflow $alur): RedirectResponse
    {
        Gate::authorize('update', $document);
        $alur->ajukan($document, $request->user());

        return back()->with('sukses', 'Dokumen diajukan untuk persetujuan.');
    }

    private function form(Request $request, ?Document $document): Response
    {
        return Inertia::render('pengajuan/form', [
            'dokumen' => $document ? $document->ringkas() + [
                'document_type_id' => $document->document_type_id,
                'berkas_nama' => $document->berkas_nama,
                'relasi' => $document->relasiKeluar->map(fn ($r) => ['jenis' => $r->jenis, 'document_id' => $r->related_document_id]),
            ] : null,
            'jenis' => DocumentType::orderBy('nama')->get(['id', 'nama', 'perlu_kadaluarsa']),
            // ponytail: seluruh dokumen yang boleh direlasikan dikirim sekaligus; ganti pencarian jarak jauh bila ribuan.
            'pilihanRelasi' => Document::visibleTo($request->user())->disetujui()
                ->when($document, fn ($q) => $q->whereKeyNot($document->id))
                ->orderByDesc('tanggal_penetapan')->get(['id', 'judul', 'nomor']),
            'ai' => [
                'aktif' => filled(config('ai.gemini.key')),
                'izinkanDivisi' => (bool) config('ai.izinkan_dokumen_divisi'),
            ],
        ]);
    }

    private function simpan(SimpanPengajuanRequest $request, Document $document, DocumentWorkflow $alur): RedirectResponse
    {
        $data = $request->safe()->except('berkas', 'relasi', 'ajukan');
        $berkasLama = null;

        if ($berkas = $request->file('berkas')) {
            $berkasLama = $document->berkas_path;
            $data += [
                'berkas_path' => $berkas->store('dokumen', 'local'),
                'berkas_nama' => $berkas->getClientOriginalName(),
                'berkas_ukuran' => $berkas->getSize(),
            ];
        }

        DB::transaction(function () use ($request, $document, $data) {
            if (! $document->exists) {
                // Divisi dan pengaju selalu dari akun, tidak pernah dari isian form.
                $document->division_id = $request->user()->division_id;
                $document->submitted_by = $request->user()->id;
            }

            $document->fill($data)->save();

            $document->relasiKeluar()->delete();
            $document->relasiKeluar()->createMany(
                collect($request->validated('relasi', []))->map(fn ($r) => ['jenis' => $r['jenis'], 'related_document_id' => $r['document_id']])
            );
        });

        if ($berkasLama) {
            Storage::disk('local')->delete($berkasLama);
        }

        if ($request->boolean('ajukan')) {
            try {
                $alur->ajukan($document, $request->user());
            } catch (TransisiTidakSah $e) {
                return to_route('pengajuan.index')->with('galat', 'Tersimpan sebagai draf. '.$e->getMessage());
            }

            return to_route('pengajuan.index')->with('sukses', 'Dokumen disimpan dan diajukan untuk persetujuan.');
        }

        return to_route('pengajuan.index')->with('sukses', 'Draf disimpan.');
    }
}
