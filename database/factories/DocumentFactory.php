<?php

namespace Database\Factories;

use App\Enums\Akses;
use App\Enums\Peran;
use App\Enums\StatusDokumen;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'judul' => fake()->sentence(6),
            'nomor' => fake()->unique()->numerify('DOK/###/####'),
            'document_type_id' => DocumentType::factory(),
            'division_id' => Division::factory(),
            // Pengaju bawaan berada di divisi yang sama dengan dokumennya.
            'submitted_by' => fn (array $a) => User::factory()->state(['peran' => Peran::Karyawan, 'division_id' => $a['division_id']]),
            'akses' => Akses::Divisi,
            'status' => StatusDokumen::Draf,
            'tanggal_penetapan' => fake()->dateTimeBetween('-5 years')->format('Y-m-d'),
            'abstrak' => fake()->paragraph(),
            'berkas_path' => 'dokumen/contoh.pdf',
            'berkas_nama' => 'contoh.pdf',
            'berkas_ukuran' => 1024,
        ];
    }

    public function status(StatusDokumen $status): static
    {
        return $this->state([
            'status' => $status,
            'disetujui_pada' => $status === StatusDokumen::Disetujui ? now() : null,
        ]);
    }

    public function disetujui(): static
    {
        return $this->status(StatusDokumen::Disetujui);
    }

    public function publik(): static
    {
        return $this->state(['akses' => Akses::Publik]);
    }
}
