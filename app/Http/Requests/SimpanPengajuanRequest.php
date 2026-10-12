<?php

namespace App\Http\Requests;

use App\Enums\Akses;
use App\Enums\JenisRelasi;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dokumen = $this->route('document');

        return $dokumen === null || $this->user()->can('update', $dokumen);
    }

    public function rules(): array
    {
        /** @var Document|null $dokumen */
        $dokumen = $this->route('document');
        $perluKadaluarsa = (bool) DocumentType::find($this->input('document_type_id'))?->perlu_kadaluarsa;

        return [
            'akses' => ['required', Rule::enum(Akses::class)],
            // mimetypes memeriksa isi berkas, bukan hanya ekstensinya.
            'berkas' => [$dokumen ? 'nullable' : 'required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:20480'],
            'judul' => ['required', 'string', 'max:255'],
            'nomor' => ['required', 'string', 'max:100', Rule::unique('documents', 'nomor')->withoutTrashed()->ignore($dokumen)],
            'document_type_id' => ['required', Rule::exists('document_types', 'id')],
            'tanggal_penetapan' => ['required', 'date'],
            'tanggal_kadaluarsa' => [$perluKadaluarsa ? 'required' : 'nullable', 'date', 'after:tanggal_penetapan'],
            'abstrak' => ['required', 'string', 'max:5000'],
            'subjek' => ['nullable', 'string', 'max:150'],
            'ajukan' => ['boolean'],
            'relasi' => ['array', 'max:20'],
            'relasi.*.jenis' => ['required', Rule::enum(JenisRelasi::class)],
            'relasi.*.document_id' => [
                'required', 'integer', 'distinct',
                // Hanya dokumen disetujui yang boleh dilihat pengaju, dan bukan dokumen ini sendiri.
                Rule::in(Document::visibleTo($this->user())->disetujui()->when($dokumen, fn ($q) => $q->whereKeyNot($dokumen->id))->pluck('id')),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'berkas' => 'berkas dokumen',
            'document_type_id' => 'jenis dokumen',
            'tanggal_penetapan' => 'tanggal penetapan',
            'tanggal_kadaluarsa' => 'tanggal kadaluarsa',
            'relasi.*.document_id' => 'dokumen terkait',
            'relasi.*.jenis' => 'jenis relasi',
        ];
    }

    public function messages(): array
    {
        return [
            'berkas.mimes' => 'Berkas harus berupa PDF.',
            'berkas.mimetypes' => 'Berkas harus berupa PDF.',
            'berkas.max' => 'Ukuran berkas maksimal 20 MB.',
            'berkas.uploaded' => 'Berkas gagal diunggah. Ukuran maksimal 20 MB.',
            'relasi.*.document_id.in' => 'Dokumen terkait tidak ditemukan.',
        ];
    }
}
