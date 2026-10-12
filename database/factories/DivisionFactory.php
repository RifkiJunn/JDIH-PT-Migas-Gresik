<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Division> */
class DivisionFactory extends Factory
{
    public function definition(): array
    {
        return ['nama' => 'Divisi '.fake()->unique()->numerify('#####')];
    }
}
