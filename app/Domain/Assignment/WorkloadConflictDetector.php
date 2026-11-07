<?php

declare(strict_types=1);

namespace App\Domain\Assignment;

use App\Domain\Shared\DateRange;

/**
 * Mencari penugasan lain seorang pegawai yang beririsan dengan rentang pelatihan
 * (awal pendaftaran sampai akhir pelatihan, K8), lintas tim kerja.
 */
final class WorkloadConflictDetector
{
    /**
     * @param  list<AssignedTraining>  $assignments  penugasan yang sudah ada (pegawai mana pun)
     * @return list<AssignedTraining> penugasan pegawai tersebut di pelatihan lain yang beririsan
     */
    public static function conflictsFor(int $employeeId, ?DateRange $range, int $trainingId, array $assignments): array
    {
        if ($range === null) {
            return [];
        }

        $conflicts = [];

        foreach ($assignments as $assignment) {
            if ($assignment->employeeId === $employeeId
                && $assignment->trainingId !== $trainingId
                && $assignment->range !== null
                && $assignment->range->overlaps($range)) {
                $conflicts[] = $assignment;
            }
        }

        usort($conflicts, fn (AssignedTraining $a, AssignedTraining $b): int => [$a->range?->start, $a->trainingId] <=> [$b->range?->start, $b->trainingId]);

        return $conflicts;
    }
}
