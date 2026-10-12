<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Peran;
use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PenggunaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/pengguna', [
            'pengguna' => User::with('division')->orderBy('name')->get()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'peran' => $u->peran,
                'division_id' => $u->division_id,
                'divisi' => $u->division?->nama,
            ]),
            'divisi' => Division::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validasi($request, null));

        return back()->with('sukses', 'Akun dibuat.');
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $data = $this->validasi($request, $pengguna);

        if ($pengguna->is($request->user()) && $data['peran'] !== Peran::Admin->value) {
            throw ValidationException::withMessages(['peran' => 'Anda tidak bisa mengubah peran akun sendiri.']);
        }

        $pengguna->update($data);

        return back()->with('sukses', 'Akun diperbarui.');
    }

    private function validasi(Request $request, ?User $pengguna): array
    {
        $perluDivisi = in_array($request->input('peran'), [Peran::Karyawan->value, Peran::Manajer->value], true);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($pengguna)],
            // Saat mengubah akun, password kosong berarti tidak diganti.
            'password' => [$pengguna ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'peran' => ['required', Rule::enum(Peran::class)],
            'division_id' => [$perluDivisi ? 'required' : 'nullable', Rule::exists('divisions', 'id')],
        ], [], ['name' => 'nama', 'division_id' => 'divisi']);

        if (! $perluDivisi) {
            $data['division_id'] = null;
        }

        return array_filter($data, fn ($v, $k) => $k !== 'password' || filled($v), ARRAY_FILTER_USE_BOTH);
    }
}
