<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\RedirectResponse;

/** Perpindahan status yang tidak diizinkan. Pesannya ditujukan untuk pengguna. */
class TransisiTidakSah extends DomainException
{
    public function render(): RedirectResponse
    {
        return back()->with('galat', $this->getMessage());
    }
}
