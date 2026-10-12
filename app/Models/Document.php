<?php

namespace App\Models;

use App\Enums\Akses;
use App\Enums\JenisRelasi;
use App\Enums\Peran;
use App\Enums\StatusDokumen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
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
            'boleh_unduh' => $user?->can('download', $this) ?? (new \App\Policies\DocumentPolicy)->download(null, $this),
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
