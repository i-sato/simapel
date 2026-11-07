<?php

declare(strict_types=1);

namespace App\Application\Assignment;

use App\Application\Assignment\Contracts\AssignmentCandidateRepository;
use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Training\Contracts\TrainingRepository;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\Exceptions\TrainingNotFound;
use App\Application\Training\TrainingActorProvider;
use App\Domain\Assignment\AssignedTraining;
use App\Domain\Assignment\AssignmentConflict;
use App\Domain\Assignment\AssignmentPlan;
use App\Domain\Assignment\AssignmentRejected;
use App\Domain\Assignment\WorkloadConflictDetector;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Training\TrainingPermissions;

/**
 * Menyimpan susunan Admin dan Panitia satu pelatihan (K4, K8).
 */
final readonly class UpdateTrainingAssignments
{
    public function __construct(
        private TrainingRepository $trainings,
        private AssignmentRepository $assignments,
        private AssignmentCandidateRepository $candidates,
        private TrainingActorProvider $actors,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    /**
     * @param  list<int>  $adminIds
     * @param  list<int>  $panitiaIds
     *
     * @throws TrainingAccessDenied|TrainingNotFound|AssignmentRejected|AssignmentConflict
     */
    public function handle(int $actorId, int $trainingId, array $adminIds, array $panitiaIds, ?string $forceReason): AssignmentPlan
    {
        return $this->transaction->run(function () use ($actorId, $trainingId, $adminIds, $panitiaIds, $forceReason): AssignmentPlan {
            // Mengunci pelatihan menyerialkan perubahan penugasannya.
            $training = $this->trainings->findForUpdate($trainingId) ?? throw new TrainingNotFound;

            if (! TrainingPermissions::canManageAssignments($this->actors->forUser($actorId), $training)) {
                throw new TrainingAccessDenied;
            }

            $current = $this->assignments->forTraining($trainingId);
            $employeeIds = array_values(array_unique([...$adminIds, ...$panitiaIds]));
            $range = $training->details->schedule->assignmentRange();
            $others = $range === null || $employeeIds === [] ? [] : $this->assignments->overlapping($range, $employeeIds);

            $conflicts = [];

            foreach ($employeeIds as $employeeId) {
                $found = WorkloadConflictDetector::conflictsFor($employeeId, $range, $trainingId, $others);

                if ($found !== []) {
                    $conflicts[$employeeId] = $found;
                }
            }

            $plan = AssignmentPlan::make(
                $trainingId,
                $current,
                $adminIds,
                $panitiaIds,
                $this->candidates->find($employeeIds),
                $conflicts,
                $forceReason,
            );

            foreach ($plan->removed as $assignment) {
                $this->assignments->remove($assignment);
                $this->audit->record(new AuditEvent(
                    type: AuditEventType::TrainingAssignmentRemoved,
                    description: sprintf('Penugasan %s dicabut dari pelatihan "%s".', $assignment->role->label(), $training->details->name),
                    actorId: $actorId,
                    subjectType: 'training',
                    subjectId: $trainingId,
                    context: ['employee_id' => $assignment->employeeId, 'role' => $assignment->role->value],
                ));
            }

            foreach ($plan->added as $assignment) {
                $this->assignments->add($assignment, $actorId);
                $found = $plan->conflicts[$assignment->key()] ?? [];
                $this->audit->record(new AuditEvent(
                    type: AuditEventType::TrainingAssignmentAdded,
                    description: sprintf(
                        'Penugasan %s ditambahkan pada pelatihan "%s"%s.',
                        $assignment->role->label(),
                        $training->details->name,
                        $found === [] ? '' : ' (force karena bentrok)',
                    ),
                    actorId: $actorId,
                    subjectType: 'training',
                    subjectId: $trainingId,
                    context: [
                        'employee_id' => $assignment->employeeId,
                        'role' => $assignment->role->value,
                        'force_reason' => $assignment->forceReason,
                        'conflict_count' => count($found),
                        'conflict_training_ids' => $found === [] ? null : implode(',', array_map(fn (AssignedTraining $other): int => $other->trainingId, $found)),
                    ],
                ));
            }

            return $plan;
        });
    }
}
