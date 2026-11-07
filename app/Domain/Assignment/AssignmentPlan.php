<?php

declare(strict_types=1);

namespace App\Domain\Assignment;

/**
 * Perubahan penugasan satu pelatihan: membandingkan susunan sekarang dengan yang diinginkan,
 * memeriksa kelayakan, dan mewajibkan alasan force untuk penugasan baru yang bentrok.
 */
final readonly class AssignmentPlan
{
    /**
     * @param  list<TrainingAssignment>  $added
     * @param  list<TrainingAssignment>  $removed
     * @param  array<string, list<AssignedTraining>>  $conflicts  key penugasan baru => bentroknya
     */
    private function __construct(
        public array $added,
        public array $removed,
        public array $conflicts,
    ) {}

    /**
     * @param  list<TrainingAssignment>  $current
     * @param  list<int>  $adminIds
     * @param  list<int>  $panitiaIds
     * @param  array<int, AssignmentCandidate>  $candidates  employeeId => kandidat, untuk penugasan baru
     * @param  array<int, list<AssignedTraining>>  $conflictsByEmployee  employeeId => bentrok
     *
     * @throws AssignmentRejected|AssignmentConflict
     */
    public static function make(
        int $trainingId,
        array $current,
        array $adminIds,
        array $panitiaIds,
        array $candidates,
        array $conflictsByEmployee,
        ?string $forceReason,
    ): self {
        $desired = [];

        foreach ([[AssignmentRole::Admin, $adminIds], [AssignmentRole::Panitia, $panitiaIds]] as [$role, $ids]) {
            foreach (array_unique($ids) as $employeeId) {
                $assignment = new TrainingAssignment($trainingId, $employeeId, $role);
                $desired[$assignment->key()] = $assignment;
            }
        }

        $existing = [];

        foreach ($current as $assignment) {
            $existing[$assignment->key()] = $assignment;
        }

        $added = [];
        $conflicts = [];

        foreach (array_diff_key($desired, $existing) as $key => $assignment) {
            $candidate = $candidates[$assignment->employeeId] ?? throw new AssignmentRejected('Pegawai tidak ditemukan.', 'panitia');
            $candidate->assertEligibleFor($assignment->role);

            $found = $conflictsByEmployee[$assignment->employeeId] ?? [];

            if ($found !== []) {
                $conflicts[$key] = $found;
            }

            $added[$key] = $assignment;
        }

        $reason = $forceReason === null ? '' : trim($forceReason);

        if ($conflicts !== [] && $reason === '') {
            throw AssignmentConflict::for($added, $conflicts, $candidates);
        }

        $withReason = [];

        foreach ($added as $key => $assignment) {
            $withReason[] = isset($conflicts[$key])
                ? new TrainingAssignment($trainingId, $assignment->employeeId, $assignment->role, $reason)
                : $assignment;
        }

        return new self($withReason, array_values(array_diff_key($existing, $desired)), $conflicts);
    }

    public function isEmpty(): bool
    {
        return $this->added === [] && $this->removed === [];
    }
}
