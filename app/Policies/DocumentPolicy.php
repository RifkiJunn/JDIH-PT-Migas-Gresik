<?php

namespace App\Policies;

use App\Enums\Akses;
use App\Enums\Peran;
use App\Enums\StatusDokumen;
use App\Models\Document;
use App\Models\User;

/**
 * Aturan bagian 3 dan 4 spec untuk satu dokumen.
 * Padanan query-nya ada di Document::scopeVisibleTo; VisibilitasTest menjaga keduanya tetap sama.
 */
class DocumentPolicy
{
    public function view(?User $user, Document $document): bool
    {
        if ($document->status === StatusDokumen::Disetujui && $document->akses === Akses::Publik) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($document->submitted_by === $user->id) {
            return true;
        }

        $sedivisi = $user->division_id !== null && $user->division_id === $document->division_id;

        return match ($document->status) {
            StatusDokumen::Disetujui => match ($user->peran) {
                Peran::Karyawan, Peran::Manajer => $sedivisi,
                Peran::Gm, Peran::Admin => true,
            },
            StatusDokumen::MenungguManajer => $user->peran === Peran::Manajer && $sedivisi,
            StatusDokumen::MenungguGm => $user->peran === Peran::Gm || ($user->peran === Peran::Manajer && $sedivisi),
            StatusDokumen::Draf, StatusDokumen::Ditolak => false,
        };
    }

    /** Admin hanya melihat metadata dokumen ber-akses Divisi, tidak berkasnya. */
    public function download(?User $user, Document $document): bool
    {
        if ($user?->peran === Peran::Admin && $document->akses === Akses::Divisi) {
            return false;
        }

        return $this->view($user, $document);
    }

    public function update(User $user, Document $document): bool
    {
        return $document->submitted_by === $user->id
            && in_array($document->status, [StatusDokumen::Draf, StatusDokumen::Ditolak], true);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    public function decide(User $user, Document $document): bool
    {
        return match ($document->status) {
            StatusDokumen::MenungguManajer => $user->peran === Peran::Manajer
                && $user->division_id === $document->division_id
                && $document->submitted_by !== $user->id,
            StatusDokumen::MenungguGm => $user->peran === Peran::Gm,
            default => false,
        };
    }
}
