<?php

declare(strict_types=1);

namespace App\Domain\Administration;

use App\Domain\Training\Training;
use App\Domain\Training\TrainingActor;

/**
 * Hak atas dokumen administrasi (K3):
 * - Ketua tim kerja terkait dan Super Admin mendelegasikan;
 * - Sekretaris yang ditugaskan pada dokumen, Ketua, dan Super Admin memperbarui.
 */
final class DocumentPermissions
{
    public static function canDelegate(TrainingActor $actor, Training $training): bool
    {
        return $actor->isActive && ($actor->isSuperAdmin || $actor->leads($training->teamId));
    }

    public static function canUpdate(TrainingActor $actor, Training $training, AdministrativeDocument $document): bool
    {
        return $actor->isActive && (
            $actor->isSuperAdmin
            || $actor->leads($training->teamId)
            || ($document->assigneeId !== null && $document->assigneeId === $actor->userId)
        );
    }
}
