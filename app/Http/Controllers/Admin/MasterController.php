<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Divisi dan jenis dokumen: dua daftar nama yang dikelola dengan cara yang sama.
 * Rute menentukan mana yang dimaksud lewat default `master` (lihat routes/web.php).
 */
class MasterController extends Controller
{
    private const MASTER = [
        'divisi' => ['model' => Division::class, 'judul' => 'Divisi', 'pakai' => ['users', 'documents']],
        'jenis-dokumen' => ['model' => DocumentType::class, 'judul' => 'Jenis Dokumen', 'pakai' => ['documents']],
    ];

    public function index(Request $request): Response
    {
        [$master, $id] = [$request->route('master'), (int) $request->route('id')];
        $m = self::MASTER[$master];

        return Inertia::render('admin/master', [
            'master' => $master,
            'judul' => $m['judul'],
            'butir' => $m['model']::withCount($m['pakai'])->orderBy('nama')->get()->map(fn (Model $b) => [
                'id' => $b->id,
                'nama' => $b->nama,
                'perlu_kadaluarsa' => $b->perlu_kadaluarsa,
                'dipakai' => collect($m['pakai'])->sum(fn ($r) => $b->{"{$r}_count"}),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$master, $id] = [$request->route('master'), (int) $request->route('id')];
        self::MASTER[$master]['model']::create($this->validasi($request, $master, null));

        return back()->with('sukses', self::MASTER[$master]['judul'].' ditambahkan.');
    }

    public function update(Request $request): RedirectResponse
    {
        [$master, $id] = [$request->route('master'), (int) $request->route('id')];
        $butir = self::MASTER[$master]['model']::findOrFail($id);
        $butir->update($this->validasi($request, $master, $butir));

        return back()->with('sukses', self::MASTER[$master]['judul'].' diperbarui.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        [$master, $id] = [$request->route('master'), (int) $request->route('id')];
        $m = self::MASTER[$master];
        $butir = $m['model']::withCount($m['pakai'])->findOrFail($id);

        if (collect($m['pakai'])->sum(fn ($r) => $butir->{"{$r}_count"}) > 0) {
            return back()->with('galat', "{$butir->nama} masih dipakai, jadi tidak bisa dihapus.");
        }

        $butir->delete();

        return back()->with('sukses', "{$butir->nama} dihapus.");
    }

    private function validasi(Request $request, string $master, ?Model $butir): array
    {
        $tabel = (new (self::MASTER[$master]['model']))->getTable();

        return $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique($tabel, 'nama')->ignore($butir)],
            ...($master === 'jenis-dokumen' ? ['perlu_kadaluarsa' => ['required', 'boolean']] : []),
        ]);
    }
}
