<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Domain\Assignment\AssignedTraining;
use App\Domain\Assignment\AssignmentRole;
use App\Domain\Assignment\TrainingAssignment;
use App\Domain\Shared\DateRange;
use App\Models\TrainingAssignmentRecord;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentAssignmentRepository implements AssignmentRepository
{
    public function forTraining(int $trainingId): array
    {
        return array_values(TrainingAssignmentRecord::query()
            ->where('training_id', $trainingId)
            ->orderBy('id')
            ->get()
            ->map(fn (TrainingAssignmentRecord $record): TrainingAssignment => new TrainingAssignment(
                $record->training_id,
                $record->user_id,
                $record->role,
                $record->force_reason,
            ))
            ->all());
    }

    public function add(TrainingAssignment $assignment, int $actorId): void
    {
        TrainingAssignmentRecord::query()->create([
            'training_id' => $assignment->trainingId,
            'user_id' => $assignment->employeeId,
            'role' => $assignment->role,
            'force_reason' => $assignment->forceReason,
            'created_by' => $actorId,
        ]);
    }

    public function remove(TrainingAssignment $assignment): void
    {
        TrainingAssignmentRecord::query()
            ->where('training_id', $assignment->trainingId)
            ->where('user_id', $assignment->employeeId)
            ->where('role', $assignment->role)
            ->delete();
    }

    public function overlapping(DateRange $range, ?array $employeeIds = null): array
    {
        $rows = DB::table('training_assignments')
            ->join('trainings', 'trainings.id', '=', 'training_assignments.training_id')
            ->join('work_teams', 'work_teams.id', '=', 'trainings.work_team_id')
            ->whereNotNull('trainings.registration_start')
            ->whereNotNull('trainings.training_end')
            ->where('trainings.registration_start', '<=', $range->end->format('Y-m-d'))
            ->where('trainings.training_end', '>=', $range->start->format('Y-m-d'))
            ->when($employeeIds !== null, fn ($query) => $query->whereIn('training_assignments.user_id', $employeeIds ?? []))
            ->orderBy('trainings.registration_start')
            ->get([
                'training_assignments.user_id',
                'training_assignments.training_id',
                'training_assignments.role',
                'trainings.name',
                'trainings.registration_start',
                'trainings.training_end',
                'work_teams.code',
            ]);

        $result = [];

        foreach ($rows as $row) {
            $result[] = new AssignedTraining(
                employeeId: (int) $row->user_id,
                trainingId: (int) $row->training_id,
                trainingName: (string) $row->name,
                teamCode: (string) $row->code,
                role: AssignmentRole::from((string) $row->role),
                range: new DateRange(new DateTimeImmutable((string) $row->registration_start), new DateTimeImmutable((string) $row->training_end)),
            );
        }

        return $result;
    }

    public function adminTrainingIdsOf(int $employeeId): array
    {
        $ids = TrainingAssignmentRecord::query()
            ->where('training_assignments.user_id', $employeeId)
            ->where('training_assignments.role', AssignmentRole::Admin)
            ->whereHas('user', fn ($user) => $user->where('can_be_admin', true))
            ->orderBy('training_assignments.training_id')
            ->pluck('training_assignments.training_id')
            ->all();

        return array_values(array_map(intval(...), $ids));
    }

    public function panitiaTrainingIdsOf(int $employeeId): array
    {
        $ids = TrainingAssignmentRecord::query()
            ->where('user_id', $employeeId)
            ->where('role', AssignmentRole::Panitia)
            ->orderBy('training_id')
            ->pluck('training_id')
            ->all();

        return array_values(array_map(intval(...), $ids));
    }
}
