<?php

namespace App\Http\Controllers;

use App\Enums\Akses;
use App\Models\DocumentType;
use App\Services\Ai\EkstraksiGagal;
use App\Services\Ai\MetadataExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EkstraksiController extends Controller
{
    public function __invoke(Request $request, MetadataExtractor $extractor): JsonResponse
    {
        $data = $request->validate([
            'berkas' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:20480'],
            'akses' => ['required', Rule::enum(Akses::class)],
        ]);

        // Dokumen divisi tidak dikirim ke luar kecuali diizinkan secara eksplisit di .env.
        abort_if($data['akses'] === Akses::Divisi->value && ! config('ai.izinkan_dokumen_divisi'), 403,
            'Isi otomatis hanya untuk dokumen berakses Publik.');

        $jenis = DocumentType::pluck('id', 'nama');

        try {
            // Berkas dibaca langsung dari unggahan sementara; tidak disimpan ke disk aplikasi.
            $mentah = $extractor->extract($request->file('berkas')->getRealPath(), $jenis->keys()->all());
        } catch (EkstraksiGagal $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->bersihkan($mentah, $jenis->all()));
    }

    /**
     * Keluaran model tidak dipercaya: tiap kolom diperiksa sendiri-sendiri,
     * yang tidak sah dikosongkan tanpa menggagalkan kolom lain.
     *
     * @param  array<string, int>  $jenis  nama => id
     */
    private function bersihkan(array $mentah, array $jenis): array
    {
        $teks = fn (string $k, int $maks) => is_string($mentah[$k] ?? null) && trim($mentah[$k]) !== ''
            ? mb_substr(trim($mentah[$k]), 0, $maks)
            : null;

        $tanggal = function (string $k) use ($mentah) {
            $v = $mentah[$k] ?? null;

            return is_string($v) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $v : null;
        };

        return [
            'judul' => $teks('judul', 255),
            'nomor' => $teks('nomor', 100),
            'document_type_id' => is_string($mentah['jenis'] ?? null) ? ($jenis[$mentah['jenis']] ?? null) : null,
            'tanggal_penetapan' => $tanggal('tanggal_penetapan'),
            'tanggal_kadaluarsa' => $tanggal('tanggal_kadaluarsa'),
            'abstrak' => $teks('abstrak', 5000),
        ];
    }
}
