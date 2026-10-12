<?php

namespace App\Services;

use App\Enums\AksiPersetujuan;
use App\Enums\Peran;
use App\Enums\StatusDokumen;
use App\Enums\Tahap;
use App\Exceptions\TransisiTidakSah;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Satu-satunya tempat status dokumen berpindah (bagian 3 spec). */
class DocumentWorkflow
{
    public function ajukan(Document $document, User $user): void
    {
        $this->terkunci($document, function (Document $dok) use ($user) {
            if ($user->cannot('update', $dok)) {
                throw new TransisiTidakSah('Dokumen ini tidak bisa diajukan.');
            }

            // Pengajuan manajer melewati tahap manajer.
            $tahap = $user->peran === Peran::Manajer ? Tahap::Gm : Tahap::Manajer;

            if ($tahap === Tahap::Manajer && ! User::where('peran', Peran::Manajer)->where('division_id', $dok->division_id)->exists()) {
                throw new TransisiTidakSah('Divisi belum memiliki manajer. Hubungi Admin.');
            }

            $dok->update(['status' => $tahap === Tahap::Gm ? StatusDokumen::MenungguGm : StatusDokumen::MenungguManajer]);
            $this->catat($dok, $user, AksiPersetujuan::Ajukan, $tahap);
        });
    }

    public function setujui(Document $document, User $user): void
    {
        $this->terkunci($document, function (Document $dok) use ($user) {
            $tahap = $this->tahapKeputusan($dok, $user);

            $dok->update($tahap === Tahap::Gm
                ? ['status' => StatusDokumen::Disetujui, 'disetujui_pada' => now()]
                : ['status' => StatusDokumen::MenungguGm]);
            $this->catat($dok, $user, AksiPersetujuan::Setuju, $tahap);
        });
    }

    public function tolak(Document $document, User $user, string $catatan): void
    {
        if (trim($catatan) === '') {
            throw new TransisiTidakSah('Catatan penolakan wajib diisi.');
        }

        $this->terkunci($document, function (Document $dok) use ($user, $catatan) {
            $tahap = $this->tahapKeputusan($dok, $user);

            $dok->update(['status' => StatusDokumen::Ditolak]);
            $this->catat($dok, $user, AksiPersetujuan::Tolak, $tahap, trim($catatan));
        });
    }

    /** Membaca ulang dokumen dengan kunci baris, supaya keputusan dari tab lama atau dua orang sekaligus tidak lolos. */
    private function terkunci(Document $document, callable $aksi): void
    {
        DB::transaction(function () use ($document, $aksi) {
            $aksi(Document::whereKey($document->id)->lockForUpdate()->firstOrFail());
        });

        $document->refresh();
    }

    private function tahapKeputusan(Document $dok, User $user): Tahap
    {
        if ($user->cannot('decide', $dok)) {
            throw new TransisiTidakSah('Dokumen ini sudah diputuskan atau bukan wewenang Anda.');
        }

        return $dok->status === StatusDokumen::MenungguGm ? Tahap::Gm : Tahap::Manajer;
    }

    private function catat(Document $dok, User $user, AksiPersetujuan $aksi, Tahap $tahap, ?string $catatan = null): void
    {
        $dok->approvalLogs()->create(['user_id' => $user->id, 'aksi' => $aksi, 'tahap' => $tahap, 'catatan' => $catatan]);
    }
}
