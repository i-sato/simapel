<?php

declare(strict_types=1);

namespace App\Application\Training;

use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Shared\Contracts\Clock;
use App\Application\Sop\ResolveSopProvision;
use App\Domain\Training\PreparationGap;
use App\Domain\Training\Training;

/**
 * Peringatan kesiapan administrasi (tidak memblokir):
 * - Final: dihitung dari salinan ketentuan SOP pada pelatihan;
 * - Draft: dihitung dari ketentuan yang berlaku hari ini untuk tim kerjanya.
 */
final readonly class TrainingPreparation
{
    public const NO_PROVISION = 'Tim kerja belum memiliki ketentuan SOP Berlaku, sehingga tenggat administrasi (BAP, SK, ST, administrasi siap) kosong.';

    public function __construct(
        private ResolveSopProvision $resolve,
        private WorkTeamRepository $teams,
        private Clock $clock,
    ) {}

    /**
     * @return list<string>
     */
    public function warningsFor(Training $training): array
    {
        if ($training->isFinal()) {
            $deadlines = $training->sopSnapshot?->deadlines;
        } else {
            $team = $this->teams->find($training->teamId);
            $deadlines = $team === null ? null : $this->resolve->handle($team->code, $this->clock->now())?->provision->deadlines;
        }

        if ($deadlines === null) {
            return [self::NO_PROVISION];
        }

        $gap = PreparationGap::warning($training->details->schedule, $deadlines);

        return $gap === null ? [] : [$gap];
    }
}
