<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini lewat Interactions API.
 * Rujukan: https://ai.google.dev/gemini-api/docs/document-processing dan /structured-output.
 */
class GeminiMetadataExtractor implements MetadataExtractor
{
    private const URL = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    public function __construct(private ?string $key, private string $model) {}

    public function extract(string $absolutePdfPath, array $namaJenis): array
    {
        if (blank($this->key)) {
            throw new EkstraksiGagal('Isi otomatis belum dikonfigurasi.');
        }

        $teks = ['type' => ['string', 'null']];

        try {
            $respons = Http::withHeaders(['x-goog-api-key' => $this->key])->timeout(90)->post(self::URL, [
                'model' => $this->model,
                // Jangan simpan berkas dan jawaban di sisi penyedia.
                'store' => false,
                'system_instruction' => 'Anda mengekstrak metadata dari dokumen perusahaan berbahasa Indonesia. '
                    .'Isi hanya yang tertulis di dokumen. Bila sebuah keterangan tidak ada, isi null; jangan menebak.',
                'input' => [
                    ['type' => 'document', 'data' => base64_encode(file_get_contents($absolutePdfPath)), 'mime_type' => 'application/pdf'],
                    ['type' => 'text', 'text' => 'Ekstrak metadata dokumen ini. Tanggal ditulis YYYY-MM-DD. '
                        .'Abstrak adalah ringkasan isi dalam 2 sampai 4 kalimat.'],
                ],
                'response_format' => [
                    'type' => 'text',
                    'mime_type' => 'application/json',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'judul' => $teks + ['description' => 'Judul lengkap dokumen, tanpa nomor.'],
                            'nomor' => $teks + ['description' => 'Nomor dokumen persis seperti tertulis.'],
                            'jenis' => ['type' => ['string', 'null'], 'enum' => [...$namaJenis, null]],
                            'tanggal_penetapan' => $teks + ['description' => 'Tanggal ditetapkan atau ditandatangani.'],
                            'tanggal_kadaluarsa' => $teks + ['description' => 'Tanggal berakhirnya masa berlaku, bila disebut.'],
                            'abstrak' => $teks,
                        ],
                        'required' => ['judul', 'nomor', 'jenis', 'tanggal_penetapan', 'tanggal_kadaluarsa', 'abstrak'],
                    ],
                ],
            ]);
        } catch (ConnectionException $e) {
            throw new EkstraksiGagal('Layanan AI tidak dapat dihubungi. Silakan isi manual.', previous: $e);
        }

        if ($respons->status() === 429) {
            throw new EkstraksiGagal('Kuota layanan AI sedang habis. Silakan isi manual.');
        }

        if ($respons->failed()) {
            // Isi balasan galat dicatat untuk admin; pengguna cukup tahu harus mengisi manual.
            Log::warning('Ekstraksi Gemini gagal', ['status' => $respons->status(), 'body' => str($respons->body())->limit(500)->value()]);

            throw new EkstraksiGagal('Isi otomatis gagal. Silakan isi manual.');
        }

        $teksKeluaran = collect($respons->json('steps', []))
            ->where('type', 'model_output')
            ->flatMap(fn ($langkah) => $langkah['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        $hasil = json_decode($teksKeluaran, true);

        if (! is_array($hasil) || array_is_list($hasil)) {
            throw new EkstraksiGagal('Berkas tidak dapat dibaca otomatis. Silakan isi manual.');
        }

        return $hasil;
    }
}
