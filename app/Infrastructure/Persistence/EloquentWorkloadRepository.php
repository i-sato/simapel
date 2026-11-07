<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Workload\Contracts\WorkloadRepository;
use App\Application\Workload\WorkloadEmployee;
use App\Domain\Organization\TeamRole;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Shared\DateRange;
use App\Domain\Workload\WorkloadItem;
use App\Domain\Workload\WorkloadRole;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Query beban kerja dengan jumlah query tetap: satu untuk penugasan Admin/Panitia,
 * satu untuk peran Ketua/Sekretaris atas pelatihan timnya.
 */
final class EloquentWorkloadRepository implements WorkloadRepository
{
    public function items(DateRange $range, ?WorkTeamCode $team = null, ?int $employeeId = null): array
    {
        $assignments = $this->trainingsOverlapping($range, $team)
            ->join('training_assignments', 'training_assignments.training_id', '=', 'trainings.id')
            ->when($employeeId !== null, fn (Builder $query) => $query->where('training_assignments.user_id', $employeeId))
            ->get(['training_assignments.user_id', 'training_assignments.role', ...self::trainingColumns()]);

        $leaders = $this->trainingsOverlapping($range, $team)
            ->join('team_memberships', 'team_memberships.work_team_id', '=', 'trainings.work_team_id')
            ->whereIn('team_memberships.role', [TeamRole::Ketua->value, TeamRole::Sekretaris->value])
            ->when($employeeId !== null, fn (Builder $query) => $query->where('team_memberships.user_id', $employeeId))
            ->get(['team_memberships.user_id', 'team_memberships.role', ...self::trainingColumns()]);

        $items = [];

        foreach ([...$assignments, ...$leaders] as $row) {
            $items[] = new WorkloadItem(
                employeeId: (int) $row->user_id,
                trainingId: (int) $row->training_id,
                trainingName: (string) $row->training_name,
                teamCode: (string) $row->team_code,
                role: WorkloadRole::from((string) $row->role),
                range: new DateRange(new DateTimeImmutable((string) $row->registration_start), new DateTimeImmutable((string) $row->training_end)),
            );
        }

        return $items;
    }

    public function employees(?WorkTeamCode $team, array $years, array $includeIds = []): array
    {
        $query = DB::table('users')->where('users.is_active', true);

        if ($team !== null) {
            $query->where(function (Builder $query) use ($team, $years, $includeIds): void {
                $query->whereIn('users.id', function (Builder $members) use ($team, $years): void {
                    $members->select('team_memberships.user_id')
                        ->from('team_memberships')
                        ->join('work_teams', 'work_teams.id', '=', 'team_memberships.work_team_id')
                        ->where('work_teams.code', $team->value)
                        ->whereIn('work_teams.year', $years);
                })->orWhereIn('users.id', $includeIds);
            });
        }

        return array_values($query->orderBy('users.name')->get(['users.id', 'users.name', 'users.jabatan'])
            ->map(fn (object $row): WorkloadEmployee => new WorkloadEmployee((int) $row->id, (string) $row->name, $row->jabatan === null ? null : (string) $row->jabatan))
            ->all());
    }

    public function employee(int $employeeId): ?WorkloadEmployee
    {
        $row = DB::table('users')->where('id', $employeeId)->first(['id', 'name', 'jabatan']);

        return $row === null ? null : new WorkloadEmployee((int) $row->id, (string) $row->name, $row->jabatan === null ? null : (string) $row->jabatan);
    }

    private function trainingsOverlapping(DateRange $range, ?WorkTeamCode $team): Builder
    {
        return DB::table('trainings')
            ->join('work_teams', 'work_teams.id', '=', 'trainings.work_team_id')
            ->whereNotNull('trainings.registration_start')
            ->whereNotNull('trainings.training_end')
            ->where('trainings.registration_start', '<=', $range->end->format('Y-m-d'))
            ->where('trainings.training_end', '>=', $range->start->format('Y-m-d'))
            ->when($team !== null, fn (Builder $query) => $query->where('work_teams.code', $team?->value));
    }

    /**
     * @return list<string>
     */
    private static function trainingColumns(): array
    {
        return [
            'trainings.id as training_id',
            'trainings.name as training_name',
            'trainings.registration_start',
            'trainings.training_end',
            'work_teams.code as team_code',
        ];
    }
}
