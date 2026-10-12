<?php

namespace App\Models;

use App\Enums\Akses;
use App\Enums\JenisRelasi;
use App\Enums\Peran;
use App\Enums\StatusDokumen;
use App\Policies\DocumentPolicy;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'akses' => Akses::class,
            'status' => StatusDokumen::class,
            'tanggal_penetapan' => 'date',
            'tanggal_kadaluarsa' => 'date',
            'disetujui_pada' => 'datetime',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function relasiKeluar(): HasMany
    {
        return $this->hasMany(DocumentRelation::class);
    }

    public function relasiMasuk(): HasMany
    {
        return $this->hasMany(DocumentRelation::class, 'related_document_id');
    }

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class)->oldest('id');
    }

    /**
     * Satu-satunya tempat aturan "siapa melihat apa" (bagian 4 spec) dalam bentuk query.
     * DocumentPolicy::view adalah padanannya untuk satu dokumen; keduanya diuji bersama.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        $query->where(function (Builder $q) use ($user) {
            $q->where(fn (Builder $q) => $q->where('status', StatusDokumen::Disetujui)->where('akses', Akses::Publik));

            if (! $user) {
                return;
            }

            // Pengaju selalu melihat pengajuannya sendiri, apa pun statusnya.
            $q->orWhere('submitted_by', $user->id);

            match ($user->peran) {
                Peran::Karyawan => $q->orWhere(fn (Builder $q) => $q
                    ->where('status', StatusDokumen::Disetujui)->where('division_id', $user->division_id)),
                Peran::Manajer => $q->orWhere(fn (Builder $q) => $q
                    ->where('division_id', $user->division_id)
                    ->whereIn('status', [StatusDokumen::Disetujui, StatusDokumen::MenungguManajer, StatusDokumen::MenungguGm])),
                Peran::Gm => $q->orWhereIn('status', [StatusDokumen::Disetujui, StatusDokumen::MenungguGm]),
                Peran::Admin => $q->orWhere('status', StatusDokumen::Disetujui),
            };
        });
    }

    public function scopeDisetujui(Builder $query): void
    {
        $query->where('status', StatusDokumen::Disetujui);
    }

    /**
     * Pencarian dan filter daftar dokumen. Nilai yang tidak sah (bukan skalar, bukan angka) diabaikan.
     * LIKE ditulis dengan ESCAPE eksplisit supaya % dan _ dari pengguna tidak menjadi wildcard
     * dan perilakunya sama di PostgreSQL, MySQL, dan SQLite.
     *
     * @param  array{q?: mixed, jenis?: mixed, tahun?: mixed, subjek?: mixed, kadaluarsa?: mixed}  $filter
     */
    public function scopeSaring(Builder $query, array $filter): void
    {
        $teks = fn (string $k) => is_string($filter[$k] ?? null) && trim($filter[$k]) !== '' ? trim($filter[$k]) : null;
        $angka = fn (string $k) => ctype_digit((string) ($teks($k) ?? '')) ? (int) $filter[$k] : null;

        if ($q = $teks('q')) {
            $pola = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($q)).'%';
            $query->where(fn (Builder $b) => $b
                ->whereRaw("LOWER(judul) LIKE ? ESCAPE '!'", [$pola])
                ->orWhereRaw("LOWER(nomor) LIKE ? ESCAPE '!'", [$pola]));
        }

        $query
            ->when($angka('jenis'), fn (Builder $b, int $id) => $b->where('document_type_id', $id))
            ->when($angka('tahun'), fn (Builder $b, int $tahun) => $b->whereYear('tanggal_penetapan', $tahun))
            ->when($teks('subjek'), fn (Builder $b, string $s) => $b->where('subjek', $s))
            ->when($teks('kadaluarsa'), fn (Builder $b) => $b->whereBetween('tanggal_kadaluarsa', [today(), today()->addDays(90)]));
    }

    /** Isi pilihan filter (jenis, tahun, subjek) dari dokumen yang ada di $query. */
    public static function pilihanFilter(Builder $query): array
    {
        $baris = $query->toBase()->get(['document_type_id', 'tanggal_penetapan', 'subjek']);

        return [
            'jenis' => DocumentType::whereIn('id', $baris->pluck('document_type_id')->unique())->orderBy('nama')->get(['id', 'nama']),
            'tahun' => $baris->map(fn ($b) => (int) substr($b->tanggal_penetapan, 0, 4))->unique()->sortDesc()->values(),
            'subjek' => $baris->pluck('subjek')->filter()->unique()->sort()->values(),
        ];
    }

    public function isBerakhir(): bool
    {
        return $this->status === StatusDokumen::Disetujui
            && $this->tanggal_kadaluarsa !== null
            && $this->tanggal_kadaluarsa->lt(today());
    }

    public function statusHukum(): string
    {
        $jenis = $this->relasiMasuk()
            ->whereHas('document', fn (Builder $q) => $q->where('status', StatusDokumen::Disetujui))
            ->pluck('jenis');

        return match (true) {
            $jenis->contains(JenisRelasi::Mencabut) => 'Dicabut',
            $jenis->contains(JenisRelasi::Menggantikan) => 'Digantikan',
            $jenis->contains(JenisRelasi::Mengubah) => 'Diubah',
            default => 'Berlaku',
        };
    }

    public function tahun(): ?int
    {
        return $this->tanggal_penetapan?->year;
    }

    /**
     * Bentuk dokumen yang dikirim ke halaman (tipe `Dokumen` di resources/js/types).
     * ponytail: statusHukum() satu query per baris; cukup untuk daftar berhalaman 20,
     * ganti dengan withExists bila daftar membesar.
     */
    public function ringkas(): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'nomor' => $this->nomor,
            'jenis' => $this->type->nama,
            'divisi' => $this->division->nama,
            'akses' => $this->akses,
            'status' => $this->status,
            'status_hukum' => $this->statusHukum(),
            'berakhir' => $this->isBerakhir(),
            'tahun' => $this->tahun(),
            'tanggal_penetapan' => $this->tanggal_penetapan?->toDateString(),
            'tanggal_kadaluarsa' => $this->tanggal_kadaluarsa?->toDateString(),
            'abstrak' => $this->abstrak,
            'subjek' => $this->subjek,
            'pengaju' => $this->pengaju->name,
        ];
    }

    /** ringkas() ditambah berkas, relasi dua arah yang boleh dilihat $user, dan riwayat persetujuan. */
    public function rinci(?User $user): array
    {
        $terlihat = fn (Builder $q) => $q->visibleTo($user)->disetujui();
        $relasi = fn (string $arah, string $kolom) => fn (DocumentRelation $r) => [
            'jenis' => $r->jenis,
            'arah' => $arah,
            'dokumen' => $r->{$kolom}->only('id', 'judul', 'nomor'),
        ];

        return $this->ringkas() + [
            'berkas_nama' => $this->berkas_nama,
            'berkas_ukuran' => $this->berkas_ukuran,
            'boleh_unduh' => $user?->can('download', $this) ?? (new DocumentPolicy)->download(null, $this),
            'relasi' => [
                ...$this->relasiKeluar()->whereHas('related', $terlihat)->with('related')->get()->map($relasi('keluar', 'related')),
                ...$this->relasiMasuk()->whereHas('document', $terlihat)->with('document')->get()->map($relasi('masuk', 'document')),
            ],
            'riwayat' => $this->approvalLogs()->with('user')->get()->map(fn (ApprovalLog $l) => [
                'id' => $l->id,
                'aksi' => $l->aksi,
                'tahap' => $l->tahap,
                'catatan' => $l->catatan,
                'oleh' => $l->user->name,
                'waktu' => $l->created_at->toIso8601String(),
            ]),
        ];
    }
}
