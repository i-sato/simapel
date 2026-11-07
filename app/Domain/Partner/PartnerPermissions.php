<?php

declare(strict_types=1);

namespace App\Domain\Partner;

use App\Domain\Training\TrainingActor;

/**
 * Menambah dan mengubah mitra (K18): Super Admin, Ketua tim kerja mana pun, dan pegawai
 * bertanda "dapat menjadi Admin" walaupun tidak sedang ditugaskan, agar pekerjaan terbagi.
 * Daftar mitra dapat dilihat semua pegawai aktif.
 */
final class PartnerPermissions
{
    public static function canManage(TrainingActor $actor): bool
    {
        return $actor->isActive && ($actor->isSuperAdmin || $actor->ledTeamIds !== [] || $actor->canBeAdmin);
    }
}
