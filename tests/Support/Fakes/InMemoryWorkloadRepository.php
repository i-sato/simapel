<?php

namespace Tests\Support\Fakes;

use App\Application\Workload\Contracts\WorkloadRepository;
use App\Application\Workload\WorkloadEmployee;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Shared\DateRange;
use App\Domain\Workload\WorkloadItem;

final class InMemoryWorkloadRepository implements WorkloadRepository
{
    /** @var list<WorkloadItem> */
    public array $items = [];

    /** @var array<int, WorkloadEmployee> */
    public array $employees = [];

    /** @var array<int, string> employeeId => kode tim */
    public array $teamOf = [];

    public function items(DateRange $range, ?WorkTeamCode $team = null, ?int $employeeId = null): array
    {
        return array_values(array_filter($this->items, fn (WorkloadItem $item): bool => $item->range->overlaps($range)
            && ($team === null || $item->teamCode === $team->value)
            && ($employeeId === null || $item->employeeId === $employeeId)));
    }

    public function employees(?WorkTeamCode $team, array $years, array $includeIds = []): array
    {
        return array_values(array_filter($this->employees, fn (WorkloadEmployee $employee): bool => $team === null
            || ($this->teamOf[$employee->id] ?? null) === $team->value
            || in_array($employee->id, $includeIds, true)));
    }

    public function employee(int $employeeId): ?WorkloadEmployee
    {
        return $this->employees[$employeeId] ?? null;
    }
}
