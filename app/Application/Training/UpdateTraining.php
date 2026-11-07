<?php

declare(strict_types=1);

namespace App\Application\Training;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Organization\Exceptions\WorkTeamNotFound;
use App\Application\Partner\PartnerResolver;
use App\Application\Shared\Contracts\Clock;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Training\Contracts\TrainingRepository;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\Exceptions\TrainingNotFound;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Training\TrainingPermissions;

/**
 * Mengubah Draft atau melengkapi pelatihan Final oleh Super Admin, Ketua tim terkait,
 * atau Admin yang ditunjuk (langkah 4).
 */
final readonly class UpdateTraining
{
    public function __construct(
        private TrainingRepository $trainings,
        private WorkTeamRepository $teams,
        private TrainingActorProvider $actors,
        private LearningPathPlacement $placement,
        private PartnerResolver $partners,
        private AuditTrail $audit,
        private Transaction $transaction,
        private Clock $clock,
    ) {}

    /**
     * @return bool true bila ada perubahan
     *
     * @throws TrainingAccessDenied|TrainingNotFound
     */
    public function handle(int $actorId, int $trainingId, int $teamId, TrainingInput $input): bool
    {
        $details = TrainingDetailsFactory::from($input);

        return $this->transaction->run(function () use ($actorId, $trainingId, $teamId, $input, $details): bool {
            $current = $this->trainings->findForUpdate($trainingId) ?? throw new TrainingNotFound;
            $actor = $this->actors->forUser($actorId);

            if (! TrainingPermissions::canEdit($actor, $current)
                || ($teamId !== $current->teamId && ! TrainingPermissions::canCreateIn($actor, $teamId))) {
                throw new TrainingAccessDenied;
            }

            $this->teams->find($teamId) ?? throw new WorkTeamNotFound;
            [$partnerId, $region] = $this->partners->resolve($actor, $input->partnerName, $details->region);
            $details = $details->withPartner($partnerId, $region);

            $pathId = $this->placement->place($input, $teamId, $details, $trainingId, $actorId);
            $revised = $current->revise($teamId, $details, $pathId, $this->clock->now());
            $changes = TrainingAudit::changes($current, $revised);

            if ($changes === []) {
                return false;
            }

            $this->trainings->save($revised, $actorId);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::TrainingUpdated,
                description: sprintf('Pelatihan %s (%s) diubah.', TrainingAudit::label($revised), $revised->status->label()),
                actorId: $actorId,
                subjectType: 'training',
                subjectId: $trainingId,
                context: $changes,
            ));

            return true;
        });
    }
}
