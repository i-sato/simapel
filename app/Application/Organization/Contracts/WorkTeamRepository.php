<?php

declare(strict_types=1);

namespace App\Application\Organization\Contracts;

use App\Application\Organization\WorkTeamSummary;
use App\Domain\Organization\TeamComposition;
use App\Domain\Organization\TeamRole;

interface WorkTeamRepository
{
    public function find(int $teamId): ?WorkTeamSummary;

    /**
     * Susunan anggota saat ini. Implementasi wajib mengunci tim agar perubahan
     * bersamaan tidak menghasilkan dua Ketua/Sekretaris.
     */
    public function compositionForUpdate(int $teamId): TeamComposition;

    public function saveMember(int $teamId, int $employeeId, TeamRole $role): void;

    public function removeMember(int $teamId, int $employeeId): void;

    public function setRegistrationGuideUrl(int $teamId, ?string $url): void;
}
