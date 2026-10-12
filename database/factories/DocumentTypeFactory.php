<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\DocumentType> */
class DocumentTypeFactory extends Factory
{
    public function definition(): array
    {
        return ['nama' => 'Jenis '.fake()->unique()->numerify('#####'), 'perlu_kadaluarsa' => false];
    }
}
