<?php

declare(strict_types=1);

namespace App\Application\Training;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Organization\Exceptions\WorkTeamNotFound;
use App\Application\Shared\Contracts\Clock;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Sop\ResolveSopProvision;
use App\Application\Training\Contracts\TrainingRepository;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\Exceptions\TrainingNotFound;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Training\SopSnapshot;
use App\Domain\Training\TrainingIncomplete;
use App\Domain\Training\TrainingLocked;
use App\Domain\Training\TrainingPermissions;

/**
 * Memfinalisasi pelatihan dan menyimpan salinan ketentuan SOP tim kerja yang berlaku
 * pada tanggal finalisasi (ADR 0010). Peringatan kesiapan administrasi dikembalikan
 * tanpa memblokir.
 */
final readonly class FinalizeTraining
{
    public function __construct(
        private TrainingRepository $trainings,
        private WorkTeamRepository $teams,
        private TrainingActorProvider $actors,
        private ResolveSopProvision $resolve,
        private TrainingPreparation $preparation,
        private AuditTrail $audit,
        private Transaction $transaction,
        private Clock $clock,
    ) {}

    /**
     * @return list<string> peringatan
     *
     * @throws TrainingAccessDenied|TrainingNotFound|TrainingIncomplete|TrainingLocked
     */
    public function handle(int $actorId, int $trainingId): array
    {
        return $this->transaction->run(function () use ($actorId, $trainingId): array {
            $draft = $this->trainings->findForUpdate($trainingId) ?? throw new TrainingNotFound;

            if (! TrainingPermissions::canFinalize($this->actors->forUser($actorId), $draft)) {
                throw new TrainingAccessDenied;
            }

            $team = $this->teams->find($draft->teamId) ?? throw new WorkTeamNotFound;
            $now = $this->clock->now();
            $resolved = $this->resolve->handle($team->code, $now);
            $final = $draft->finalize($resolved === null ? null : SopSnapshot::fromResolved($resolved), $now);
            $this->trainings->save($final, $actorId);

            $warnings = $this->preparation->warningsFor($final);
            $snapshot = $final->sopSnapshot;

            $this->audit->record(new AuditEvent(
                type: AuditEventType::TrainingFinalized,
                description: sprintf('Pelatihan %s difinalisasi.', TrainingAudit::label($final)),
                actorId: $actorId,
                subjectType: 'training',
                subjectId: $trainingId,
                context: [
                    'sop_provision_id' => $snapshot?->provisionId,
                    'sop_fiscal_year' => $snapshot?->fiscalYear,
                    'sop_version' => $snapshot?->version,
                    'sop_is_fallback' => $snapshot?->isFallback,
                    'ready_days' => $snapshot?->deadlines->readyDays,
                    'preparation_days' => $final->details->schedule->preparationDays(),
                    'warnings' => count($warnings),
                ],
            ));

            return $warnings;
        });
    }
}
