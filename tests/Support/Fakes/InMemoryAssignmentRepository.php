<?php

namespace Tests\Support\Fakes;

use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Domain\Assignment\AssignedTraining;
use App\Domain\Assignment\AssignmentRole;
use App\Domain\Assignment\TrainingAssignment;
use App\Domain\Shared\DateRange;

/**
 * Penugasan di memori; rentang pelatihan dibaca dari fake repository pelatihan dan tim.
 */
final class InMemoryAssignmentRepository implements AssignmentRepository
{
    /** @var array<string, TrainingAssignment> */
    public array $assignments = [];

    public function __construct(
        private readonly InMemoryTrainingRepository $trainings,
        private readonly InMemoryWorkTeamRepository $teams,
        private readonly ?InMemoryAssignmentCandidateRepository $candidates = null,
    ) {}

    public function forTraining(int $trainingId): array
    {
        return array_values(array_filter($this->assignments, fn (TrainingAssignment $assignment): bool => $assignment->trainingId === $trainingId));
    }

    public function add(TrainingAssignment $assignment, int $actorId): void
    {
        $this->assignments[$assignment->trainingId.':'.$assignment->key()] = $assignment;
    }

    public function remove(TrainingAssignment $assignment): void
    {
        unset($this->assignments[$assignment->trainingId.':'.$assignment->key()]);
    }

    public function overlapping(DateRange $range, ?array $employeeIds = null): array
    {
        $result = [];

        foreach ($this->assignments as $assignment) {
            if ($employeeIds !== null && ! in_array($assignment->employeeId, $employeeIds, true)) {
                continue;
            }

            $training = $this->trainings->find($assignment->trainingId);
            $trainingRange = $training?->details->schedule->assignmentRange();

            if ($training === null || $trainingRange === null || ! $trainingRange->overlaps($range)) {
                continue;
            }

            $result[] = new AssignedTraining(
                $assignment->employeeId,
                $assignment->trainingId,
                $training->details->name,
                $this->teams->find($training->teamId)->code->value ?? '?',
                $assignment->role,
                $trainingRange,
            );
        }

        return $result;
    }

    public function adminTrainingIdsOf(int $employeeId): array
    {
        if ($this->candidates !== null && ! ($this->candidates->candidates[$employeeId]->canBeAdmin ?? false)) {
            return [];
        }

        $ids = [];

        foreach ($this->assignments as $assignment) {
            if ($assignment->employeeId === $employeeId && $assignment->role === AssignmentRole::Admin) {
                $ids[] = $assignment->trainingId;
            }
        }

        sort($ids);

        return $ids;
    }
}
