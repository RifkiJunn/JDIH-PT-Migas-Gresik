<?php

namespace Database\Factories;

use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Division> */
class DivisionFactory extends Factory
{
    public function definition(): array
    {
        return ['nama' => 'Divisi '.fake()->unique()->numerify('#####')];
    }
}
