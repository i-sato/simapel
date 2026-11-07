<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Application\Administration\Contracts\AdministrativeDocumentRepository;
use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Application\Assignment\TrainingWorkload;
use App\Application\Settings\GlobalSettings;
use App\Application\Shared\Contracts\Clock;
use App\Domain\Administration\TrainingAlert;
use App\Domain\Administration\TrainingAlertInput;
use App\Domain\Administration\TrainingAlerts;
use App\Domain\Assignment\TrainingAssignment;
use App\Domain\Training\Training;

/**
 * Mengumpulkan fakta pelatihan lalu menghitung peringatannya lewat Domain.
 */
final readonly class TrainingAlertsQuery
{
    public function __construct(
        private AdministrativeDocumentRepository $documents,
        private AssignmentRepository $assignments,
        private TrainingWorkload $workload,
        private GlobalSettings $settings,
        private Clock $clock,
    ) {}

    /**
     * @return list<TrainingAlert>
     */
    public function for(Training $training): array
    {
        $id = (int) $training->id;
        $assigned = array_values(array_unique(array_map(
            fn (TrainingAssignment $assignment): int => $assignment->employeeId,
            $this->assignments->forTraining($id),
        )));

        return TrainingAlerts::for(new TrainingAlertInput(
            isFinal: $training->isFinal(),
            schedule: $training->details->schedule,
            deadlines: $training->sopSnapshot?->deadlines,
            documents: $training->isFinal() ? $this->documents->forTraining($id) : [],
            hasParticipantList: $training->details->participantListUrl !== null,
            closingSoonDays: $this->settings->closingSoonDays(),
            conflictCount: $assigned === [] ? 0 : count($this->workload->conflictsFor($training, $assigned)),
        ), $this->clock->now());
    }
}
