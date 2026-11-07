<?php

declare(strict_types=1);

namespace App\Application\Training;

use App\Domain\Training\Training;

/**
 * Konteks audit pelatihan (tanpa data pribadi).
 */
final class TrainingAudit
{
    /**
     * @return array<string, scalar|null>
     */
    public static function snapshot(Training $training): array
    {
        $details = $training->details;
        $schedule = $details->schedule;

        return [
            'name' => $details->name,
            'work_team_id' => $training->teamId,
            'status' => $training->status->value,
            'registration_url' => $details->registrationUrl?->value,
            'registration_start' => $schedule->registrationStart?->format('Y-m-d'),
            'registration_end' => $schedule->registrationEnd?->format('Y-m-d'),
            'training_start' => $schedule->trainingStart?->format('Y-m-d'),
            'training_end' => $schedule->trainingEnd?->format('Y-m-d'),
            'partner_location' => $details->partnerLocation,
            'participant_list_url' => $details->participantListUrl?->value,
            'method' => $details->method?->value,
            'batch' => $details->batch,
            'syllabus_url' => $details->syllabusUrl?->value,
            'level' => $details->level?->value,
            'certification' => $details->certification?->value,
            'coc_shows_score' => $details->cocShowsScore,
            'execution' => $details->execution?->value,
            'participant_category' => $details->participantCategory?->value,
            'zonation' => $details->zonation?->value,
            'lesson_hours' => $details->lessonHours,
            'participant_target' => $details->participantTarget,
            'registration_quota' => $details->registrationQuota,
            'learning_path_id' => $training->learningPathId,
        ];
    }

    /**
     * Nilai lama dan baru untuk field yang berubah saja.
     *
     * @return array<string, scalar|null>
     */
    public static function changes(Training $before, Training $after): array
    {
        $old = self::snapshot($before);
        $new = self::snapshot($after);
        $changes = [];

        foreach ($new as $field => $value) {
            if ($old[$field] !== $value) {
                $changes['old_'.$field] = $old[$field];
                $changes['new_'.$field] = $value;
            }
        }

        return $changes;
    }

    public static function label(Training $training): string
    {
        return sprintf('"%s"', $training->details->name);
    }
}
