<?php

declare(strict_types=1);

namespace App\Application\Sop;

use App\Application\Shared\Contracts\Clock;
use App\Application\Sop\Contracts\SopProvisionRepository;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\ResolvedSopProvision;
use App\Domain\Sop\SopProvisionResolver;
use DateTimeImmutable;

/**
 * Ketentuan yang berlaku untuk tim kerja pada tanggal tertentu (default: hari ini).
 * Dipakai finalisasi pelatihan (langkah 3) dan badge peringatan.
 */
final readonly class ResolveSopProvision
{
    public function __construct(
        private SopProvisionRepository $provisions,
        private Clock $clock,
    ) {}

    public function handle(WorkTeamCode $team, ?DateTimeImmutable $on = null): ?ResolvedSopProvision
    {
        return SopProvisionResolver::resolve($this->provisions->forTeam($team), $on ?? $this->clock->now());
    }
}
