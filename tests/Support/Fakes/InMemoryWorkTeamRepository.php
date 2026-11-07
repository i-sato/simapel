<?php

namespace Tests\Support\Fakes;

use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Organization\WorkTeamSummary;
use App\Domain\Organization\TeamComposition;
use App\Domain\Organization\TeamRole;
use App\Domain\Organization\WorkTeamCode;

final class InMemoryWorkTeamRepository implements WorkTeamRepository
{
    /** @var array<int, WorkTeamSummary> */
    public array $teams = [];

    /** @var array<int, array<int, TeamRole>> teamId => [employeeId => peran] */
    public array $members = [];

    public int $locks = 0;

    public function addTeam(WorkTeamCode $code = WorkTeamCode::Dta, int $year = 2026): int
    {
        $id = count($this->teams) + 1;
        $this->teams[$id] = new WorkTeamSummary($id, $code, $code->defaultName(), $year);
        $this->members[$id] = [];

        return $id;
    }

    public function find(int $teamId): ?WorkTeamSummary
    {
        return $this->teams[$teamId] ?? null;
    }

    public function compositionForUpdate(int $teamId): TeamComposition
    {
        $this->locks++;

        return new TeamComposition($this->members[$teamId] ?? []);
    }

    public function saveMember(int $teamId, int $employeeId, TeamRole $role): void
    {
        $this->members[$teamId][$employeeId] = $role;
    }

    public function removeMember(int $teamId, int $employeeId): void
    {
        unset($this->members[$teamId][$employeeId]);
    }

    public function setRegistrationGuideUrl(int $teamId, ?string $url): void
    {
        $team = $this->teams[$teamId];
        $this->teams[$teamId] = new WorkTeamSummary($team->id, $team->code, $team->name, $team->year, $url);
    }

    public function teamIdsLedBy(int $employeeId): array
    {
        $ids = [];

        foreach ($this->members as $teamId => $members) {
            if (($members[$employeeId] ?? null) === TeamRole::Ketua) {
                $ids[] = $teamId;
            }
        }

        return $ids;
    }
}
