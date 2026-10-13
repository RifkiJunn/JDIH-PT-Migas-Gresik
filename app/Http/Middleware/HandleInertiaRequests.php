<?php

namespace App\Http\Middleware;

use App\Models\DocumentType;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'peran' => $user->peran,
                    'division' => $user->division?->only('id', 'nama'),
                ] : null,
            ],
            'flash' => [
                'sukses' => $request->session()->get('sukses'),
                'galat' => $request->session()->get('galat'),
            ],
            // Menu dan kaki portal publik: jenis yang punya dokumen publik, beserta jumlahnya.
            // ponytail: satu query per halaman penuh; beri cache bila lalu lintas publik ramai.
            'jenisPublik' => fn () => DocumentType::query()
                ->withCount(['documents as jumlah' => fn ($q) => $q->visibleTo(null)->disetujui()])
                ->orderBy('nama')->get(['id', 'nama'])
                ->where('jumlah', '>', 0)->values()
                ->map->only('id', 'nama', 'jumlah'),
            'kontak' => array_filter(config('jdih.kontak')),
        ];
    }
}
