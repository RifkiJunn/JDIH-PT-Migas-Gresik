<?php

namespace App\Models;

use App\Enums\Peran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'peran', 'division_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'peran' => Peran::class,
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function isPeran(Peran ...$peran): bool
    {
        return in_array($this->peran, $peran, true);
    }

    /** Halaman pertama setelah login, menurut peran. */
    public function beranda(): string
    {
        return match ($this->peran) {
            Peran::Karyawan => '/arsip',
            Peran::Manajer, Peran::Gm => '/persetujuan',
            Peran::Admin => '/admin/pengguna',
        };
    }
}
