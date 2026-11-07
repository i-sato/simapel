<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Organization\WorkTeamSummary;
use App\Domain\Organization\TeamComposition;
use App\Domain\Organization\TeamRole;
use App\Models\TeamMembership;
use App\Models\WorkTeam;

final class EloquentWorkTeamRepository implements WorkTeamRepository
{
    public function find(int $teamId): ?WorkTeamSummary
    {
        $team = WorkTeam::query()->find($teamId);

        return $team === null ? null : new WorkTeamSummary($team->id, $team->code, $team->name, $team->year, $team->registration_guide_url);
    }

    public function compositionForUpdate(int $teamId): TeamComposition
    {
        // Mengunci baris tim menyerialkan semua perubahan susunan anggota tim ini.
        WorkTeam::query()->whereKey($teamId)->lockForUpdate()->first();

        $members = [];

        foreach (TeamMembership::query()->where('work_team_id', $teamId)->get(['user_id', 'role']) as $membership) {
            $members[$membership->user_id] = $membership->role;
        }

        return new TeamComposition($members);
    }

    public function saveMember(int $teamId, int $employeeId, TeamRole $role): void
    {
        TeamMembership::query()->updateOrCreate(
            ['work_team_id' => $teamId, 'user_id' => $employeeId],
            ['role' => $role],
        );
    }

    public function removeMember(int $teamId, int $employeeId): void
    {
        TeamMembership::query()
            ->where('work_team_id', $teamId)
            ->where('user_id', $employeeId)
            ->delete();
    }

    public function setRegistrationGuideUrl(int $teamId, ?string $url): void
    {
        WorkTeam::query()->whereKey($teamId)->update(['registration_guide_url' => $url]);
    }
}
