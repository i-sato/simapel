<?php

declare(strict_types=1);

namespace App\View;

use App\Domain\Organization\TeamRole;
use App\Models\TeamMembership;

/**
 * Badge peran tim kerja per pegawai, mis. "Ketua Tim DTA", "Sekretaris Tim KDD".
 */
final class TeamRoleBadges
{
    /**
     * @return array<int, list<string>> userId => label
     */
    public function forYear(int $year): array
    {
        $badges = [];

        $memberships = TeamMembership::query()
            ->whereIn('role', [TeamRole::Ketua, TeamRole::Sekretaris])
            ->whereHas('team', fn ($team) => $team->where('year', $year))
            ->with('team')
            ->get();

        foreach ($memberships as $membership) {
            $badges[$membership->user_id][] = sprintf('%s Tim %s', $membership->role->label(), $membership->team->code->value);
        }

        return $badges;
    }
}
