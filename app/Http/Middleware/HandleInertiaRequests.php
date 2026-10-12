<?php

namespace App\Http\Middleware;

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
        ];
    }
}
