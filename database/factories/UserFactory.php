<?php

namespace Database\Factories;

use App\Enums\Peran;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'peran' => Peran::Karyawan,
            'division_id' => Division::factory(),
        ];
    }

    public function karyawan(?Division $division = null): static
    {
        return $this->state(['peran' => Peran::Karyawan, 'division_id' => $division ?? Division::factory()]);
    }

    public function manajer(?Division $division = null): static
    {
        return $this->state(['peran' => Peran::Manajer, 'division_id' => $division ?? Division::factory()]);
    }

    public function gm(): static
    {
        return $this->state(['peran' => Peran::Gm, 'division_id' => null]);
    }

    public function admin(): static
    {
        return $this->state(['peran' => Peran::Admin, 'division_id' => null]);
    }
}
