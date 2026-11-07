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
 * Admin yang ditunjuk pada pelatihan tersebut diisi dari penugasan Admin (langkah 4)
 * ke TrainingActor::$assignedAdminTrainingIds.
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

    /**
     * Mengatur penugasan Admin/Panitia: Ketua tim kerja terkait dan Super Admin.
     */
    public static function canManageAssignments(TrainingActor $actor, Training $training): bool
    {
        return $actor->isActive && ($actor->isSuperAdmin || $actor->leads($training->teamId));
    }

    /**
     * Template WA (K9, langkah 5): Panitia, Ketua tim terkait, dan Admin yang ditugaskan
     * pada pelatihan tersebut; Super Admin dapat mengakses seluruh fitur (K1).
     */
    public static function canUseMessageTemplates(TrainingActor $actor, Training $training): bool
    {
        return $actor->isActive && (
            $actor->isSuperAdmin
            || $actor->leads($training->teamId)
            || $actor->isAssignedAdminOf($training->id)
            || $actor->isPanitiaOf($training->id)
        );
    }

    public static function canFinalize(TrainingActor $actor, Training $training): bool
    {
        return $actor->isActive
            && ! $training->isFinal()
            && ($actor->isSuperAdmin || $actor->isAssignedAdminOf($training->id));
    }
}
