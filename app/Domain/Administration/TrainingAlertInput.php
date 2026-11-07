<?php

declare(strict_types=1);

namespace App\Domain\Administration;

use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Training\TrainingSchedule;

/**
 * Fakta satu pelatihan yang dibutuhkan untuk menghitung peringatan.
 */
final readonly class TrainingAlertInput
{
    /**
     * @param  AdministrativeDeadlines|null  $deadlines  salinan ketentuan pada pelatihan (null bila tidak ada)
     * @param  list<AdministrativeDocument>  $documents
     */
    public function __construct(
        public bool $isFinal,
        public TrainingSchedule $schedule,
        public ?AdministrativeDeadlines $deadlines,
        public array $documents,
        public bool $hasParticipantList,
        public int $closingSoonDays,
        public int $conflictCount,
    ) {}
}
