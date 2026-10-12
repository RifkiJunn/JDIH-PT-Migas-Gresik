<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentType> */
class DocumentTypeFactory extends Factory
{
    public function definition(): array
    {
        return ['nama' => 'Jenis '.fake()->unique()->numerify('#####'), 'perlu_kadaluarsa' => false];
    }
}
