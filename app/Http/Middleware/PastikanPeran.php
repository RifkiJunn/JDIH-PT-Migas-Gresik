<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        abort_unless(in_array($request->user()?->peran->value, $peran, true), 403);

        return $next($request);
    }
}
