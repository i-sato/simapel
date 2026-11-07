<?php

declare(strict_types=1);

namespace App\Application\Assignment;

use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Domain\Assignment\AssignedTraining;
use App\Domain\Assignment\WorkloadConflictDetector;
use App\Domain\Training\Training;

/**
 * Bentrok penugasan pegawai terhadap satu pelatihan, untuk badge dan kotak peringatan.
 */
final readonly class TrainingWorkload
{
    public function __construct(private AssignmentRepository $assignments) {}

    /**
     * @param  list<int>|null  $employeeIds  null berarti semua pegawai
     * @return array<int, list<AssignedTraining>> employeeId => penugasan lain yang beririsan
     */
    public function conflictsFor(Training $training, ?array $employeeIds = null): array
    {
        $range = $training->details->schedule->assignmentRange();

        if ($range === null || $training->id === null) {
            return [];
        }

        $others = $this->assignments->overlapping($range, $employeeIds);
        $conflicts = [];

        foreach (array_unique(array_map(fn (AssignedTraining $other): int => $other->employeeId, $others)) as $employeeId) {
            $found = WorkloadConflictDetector::conflictsFor($employeeId, $range, $training->id, $others);

            if ($found !== []) {
                $conflicts[$employeeId] = $found;
            }
        }

        return $conflicts;
    }
}
