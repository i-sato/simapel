<?php

declare(strict_types=1);

namespace App\Domain\Assignment;

use App\Domain\Shared\BusinessRuleViolation;

/**
 * Penugasan baru beririsan dengan penugasan lain dan belum disertai alasan force.
 */
final class AssignmentConflict extends BusinessRuleViolation
{
    /**
     * @param  array<string, TrainingAssignment>  $added
     * @param  array<string, list<AssignedTraining>>  $conflicts
     * @param  array<int, AssignmentCandidate>  $candidates
     */
    public static function for(array $added, array $conflicts, array $candidates): self
    {
        $lines = [];

        foreach ($conflicts as $key => $found) {
            $assignment = $added[$key];
            $name = $candidates[$assignment->employeeId]->name ?? 'Pegawai';
            $lines[] = sprintf(
                '%s (%s): %s',
                $name,
                $assignment->role->label(),
                implode('; ', array_map(fn (AssignedTraining $other): string => sprintf('%s "%s"', $other->label(), $other->trainingName), $found)),
            );
        }

        return new self('Penugasan bentrok dengan pelatihan lain. Isi alasan untuk tetap menugaskan. '.implode(' | ', $lines).'.', 'force_reason');
    }
}
