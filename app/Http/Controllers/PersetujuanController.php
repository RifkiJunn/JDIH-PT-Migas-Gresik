<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Enums\StatusDokumen;
use App\Models\Document;
use App\Services\DocumentWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PersetujuanController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Antrean = dokumen yang lolos DocumentPolicy::decide untuk akun ini.
        $antrean = Document::with('type', 'division', 'pengaju')
            ->when($user->peran === Peran::Gm,
                fn ($q) => $q->where('status', StatusDokumen::MenungguGm),
                fn ($q) => $q->where('status', StatusDokumen::MenungguManajer)
                    ->where('division_id', $user->division_id)
                    ->where('submitted_by', '!=', $user->id))
            ->oldest('updated_at')
            ->get()
            ->map(fn (Document $d) => $d->ringkas() + ['diajukan' => $d->updated_at->toIso8601String()]);

        return Inertia::render('persetujuan/index', ['dokumen' => $antrean]);
    }

    public function show(Request $request, Document $document): Response
    {
        Gate::authorize('view', $document);

        return Inertia::render('persetujuan/show', [
            'dokumen' => $document->rinci($request->user()),
            'bolehMemutuskan' => $request->user()->can('decide', $document),
        ]);
    }

    public function setujui(Request $request, Document $document, DocumentWorkflow $alur): RedirectResponse
    {
        $alur->setujui($document, $request->user());

        return to_route('persetujuan.index')->with('sukses', $document->status === StatusDokumen::Disetujui
            ? 'Dokumen disetujui dan kini berlaku.'
            : 'Dokumen disetujui dan diteruskan ke General Manager.');
    }

    public function tolak(Request $request, Document $document, DocumentWorkflow $alur): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['required', 'string', 'max:2000']], [], ['catatan' => 'catatan penolakan']);
        $alur->tolak($document, $request->user(), $data['catatan']);

        return to_route('persetujuan.index')->with('sukses', 'Dokumen ditolak dan dikembalikan ke pengaju.');
    }
}
