<?php

declare(strict_types=1);

namespace App\Domain\Training;

/**
 * Hak akses pelatihan (K5, K9):
 * - semua pegawai aktif melihat daftar dan detail;
 * - Ketua tim terkait dan Super Admin membuat Draft;
 * - Super Admin, Ketua tim terkait, dan Admin yang ditunjuk mengubah Draft dan
 *   melengkapi pelatihan Final (keputusan pemilik proyek 7 November 2026);
 * - Super Admin dan Admin yang ditunjuk memfinalisasi (K5).
 *
 * Batasan isi pelatihan Final (tetap lengkap, tim dan salinan ketentuan SOP tidak
 * berubah) ditegakkan di Training::revise(), bukan di sini.
 *
 * Titik perluasan langkah 4: Admin yang ditunjuk pada pelatihan tersebut
 * (TrainingActor::$assignedAdminTrainingIds).
 */
final class TrainingPermissions
{
    public static function canView(TrainingActor $actor): bool
    {
        return $actor->isActive;
    }

    public static function canCreateIn(TrainingActor $actor, int $teamId): bool
    {
        return $actor->isActive && ($actor->isSuperAdmin || $actor->leads($teamId));
    }

    public static function canCreateAny(TrainingActor $actor): bool
    {
        return $actor->isActive && ($actor->isSuperAdmin || $actor->ledTeamIds !== []);
    }

    public static function canEdit(TrainingActor $actor, Training $training): bool
    {
        return $actor->isActive
            && ($actor->isSuperAdmin || $actor->leads($training->teamId) || $actor->isAssignedAdminOf($training->id));
    }

    public static function canFinalize(TrainingActor $actor, Training $training): bool
    {
        return $actor->isActive
            && ! $training->isFinal()
            && ($actor->isSuperAdmin || $actor->isAssignedAdminOf($training->id));
    }
}
