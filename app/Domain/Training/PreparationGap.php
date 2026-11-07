<?php

declare(strict_types=1);

namespace App\Domain\Training;

use App\Domain\Sop\AdministrativeDeadlines;

/**
 * Jeda penutupan pendaftaran → mulai pelatihan dibandingkan tenggat "administrasi siap"
 * ketentuan SOP tim kerja. Hanya peringatan, tidak memblokir finalisasi.
 */
final class PreparationGap
{
    public static function warning(TrainingSchedule $schedule, AdministrativeDeadlines $deadlines): ?string
    {
        $days = $schedule->preparationDays();

        if ($days === null || $days >= $deadlines->readyDays) {
            return null;
        }

        return sprintf(
            'Jeda penutupan pendaftaran sampai pelatihan dimulai hanya %d hari, lebih pendek dari tenggat administrasi siap H+%d pada ketentuan SOP tim kerja.',
            $days,
            $deadlines->readyDays,
        );
    }
}
