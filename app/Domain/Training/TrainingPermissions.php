<?php

declare(strict_types=1);

namespace App\Domain\Training;

/**
 * Hak akses pelatihan (K5, K9):
 * - semua pegawai aktif melihat daftar dan detail;
 * - Ketua tim terkait dan Super Admin membuat dan mengubah Draft;
 * - Super Admin memfinalisasi dan melengkapi pelatihan Final.
 *
 * Titik perluasan langkah 4: Admin yang ditugaskan pada pelatihan tersebut
 * (TrainingActor::$assignedAdminTrainingIds) ikut memfinalisasi dan melengkapi.
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
        if (! $actor->isActive) {
            return false;
        }

        if ($training->isFinal()) {
            return $actor->isSuperAdmin || $actor->isAssignedAdminOf($training->id);
        }

        return $actor->isSuperAdmin || $actor->leads($training->teamId) || $actor->isAssignedAdminOf($training->id);
    }

    public static function canFinalize(TrainingActor $actor, Training $training): bool
    {
        return $actor->isActive
            && ! $training->isFinal()
            && ($actor->isSuperAdmin || $actor->isAssignedAdminOf($training->id));
    }
}
