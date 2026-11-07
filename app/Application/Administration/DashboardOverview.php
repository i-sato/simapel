<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Application\Administration\Contracts\AdministrativeDocumentRepository;
use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Application\Shared\Contracts\Clock;
use App\Application\Training\Contracts\TrainingRepository;
use App\Domain\Administration\AdministrativeDocument;
use App\Domain\Administration\AlertKind;
use App\Domain\Administration\DocumentDeadline;
use App\Domain\Administration\TrainingAlert;
use App\Domain\Training\Training;
use App\Domain\Training\TrainingPhase;
use DateTimeImmutable;

/**
 * Ringkasan dashboard: pelatihan berjalan (dilihat semua pegawai, K9) dan
 * daftar "Perlu tindakan saya".
 */
final readonly class DashboardOverview
{
    public function __construct(
        private TrainingRepository $trainings,
        private AdministrativeDocumentRepository $documents,
        private AssignmentRepository $assignments,
        private TrainingAlertsQuery $alerts,
        private Clock $clock,
    ) {}

    /**
     * @return array{
     *     counts: array{running: int, incomplete: int, closing_soon: int, conflicts: int},
     *     running: list<array{training: Training, phase: TrainingPhase|null, alerts: list<TrainingAlert>}>,
     *     my_documents: list<array{document: AdministrativeDocument, training: Training, deadline: DateTimeImmutable|null}>,
     *     my_missing_links: list<Training>
     * }
     */
    public function for(int $userId): array
    {
        $now = $this->clock->now();
        $running = [];
        $counts = ['running' => 0, 'incomplete' => 0, 'closing_soon' => 0, 'conflicts' => 0];

        foreach ($this->trainings->running($now) as $training) {
            $alerts = $this->alerts->for($training);
            $kinds = array_map(fn (TrainingAlert $alert): AlertKind => $alert->kind, $alerts);
            $running[] = ['training' => $training, 'phase' => $training->phaseOn($now), 'alerts' => $alerts];

            $counts['running']++;
            $counts['closing_soon'] += in_array(AlertKind::ClosingSoon, $kinds, true) ? 1 : 0;
            $counts['conflicts'] += in_array(AlertKind::AssignmentConflict, $kinds, true) ? 1 : 0;

            if ($training->isFinal()) {
                $done = array_filter($this->documents->forTraining((int) $training->id), fn (AdministrativeDocument $document): bool => $document->isDone());
                $counts['incomplete'] += count($done) < 3 ? 1 : 0;
            }
        }

        return [
            'counts' => $counts,
            'running' => $running,
            'my_documents' => $this->myDocuments($userId),
            'my_missing_links' => $this->myMissingLinks($userId, $now),
        ];
    }

    /**
     * @return list<array{document: AdministrativeDocument, training: Training, deadline: DateTimeImmutable|null}>
     */
    private function myDocuments(int $userId): array
    {
        $result = [];

        foreach ($this->documents->pendingAssignedTo($userId) as $document) {
            $training = $this->trainings->find($document->trainingId);

            if ($training === null) {
                continue;
            }

            $registrationEnd = $training->details->schedule->registrationEnd;
            $deadlines = $training->sopSnapshot?->deadlines;

            $result[] = [
                'document' => $document,
                'training' => $training,
                'deadline' => $registrationEnd === null || $deadlines === null ? null : DocumentDeadline::for($document->type, $registrationEnd, $deadlines),
            ];
        }

        usort($result, fn (array $a, array $b): int => [$a['deadline'] === null, $a['deadline']] <=> [$b['deadline'] === null, $b['deadline']]);

        return $result;
    }

    /**
     * Pelatihan yang saya admin-i, pendaftarannya sudah ditutup, tetapi link daftar peserta kosong.
     *
     * @return list<Training>
     */
    private function myMissingLinks(int $userId, DateTimeImmutable $now): array
    {
        $result = [];

        foreach ($this->assignments->adminTrainingIdsOf($userId) as $trainingId) {
            $training = $this->trainings->find($trainingId);

            if ($training !== null
                && $training->details->participantListUrl === null
                && $training->details->schedule->registrationEndedOn($now)) {
                $result[] = $training;
            }
        }

        return $result;
    }
}
