<?php

namespace App\View;

use App\Application\Shared\Contracts\Clock;
use App\Application\Sop\ResolveSopProvision;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\ResolvedSopProvision;

/**
 * Status ketentuan SOP tim kerja hari ini, untuk badge "SOP belum dilampirkan"
 * dan "Ketentuan 20XX belum ditetapkan".
 */
final readonly class TeamSopStatus
{
    public function __construct(
        private ResolveSopProvision $resolve,
        private Clock $clock,
    ) {}

    /**
     * @return array{resolved: ResolvedSopProvision|null, missing: bool, fallback: bool, year: int, sopUrl: string|null, sopNumber: string|null}
     */
    public function for(WorkTeamCode $team): array
    {
        $resolved = $this->resolve->handle($team);

        return [
            'resolved' => $resolved,
            'missing' => $resolved === null || $resolved->provision->sop->url === null,
            'fallback' => $resolved->isFallback ?? false,
            'year' => (int) $this->clock->now()->format('Y'),
            'sopUrl' => $resolved?->provision->sop->url?->value,
            'sopNumber' => $resolved?->provision->sop->number,
        ];
    }

    /**
     * @return array<string, array{resolved: ResolvedSopProvision|null, missing: bool, fallback: bool, year: int, sopUrl: string|null, sopNumber: string|null}>
     */
    public function forAll(): array
    {
        $statuses = [];

        foreach (WorkTeamCode::cases() as $team) {
            $statuses[$team->value] = $this->for($team);
        }

        return $statuses;
    }
}
