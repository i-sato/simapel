<?php

declare(strict_types=1);

namespace App\Application\Training;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Organization\Exceptions\WorkTeamNotFound;
use App\Application\Shared\Contracts\Clock;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Training\Contracts\TrainingRepository;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Training\Training;
use App\Domain\Training\TrainingPermissions;

final readonly class CreateTrainingDraft
{
    public function __construct(
        private TrainingRepository $trainings,
        private WorkTeamRepository $teams,
        private TrainingActorProvider $actors,
        private LearningPathPlacement $placement,
        private AuditTrail $audit,
        private Transaction $transaction,
        private Clock $clock,
    ) {}

    /**
     * @throws TrainingAccessDenied
     */
    public function handle(int $actorId, int $teamId, TrainingInput $input): int
    {
        if (! TrainingPermissions::canCreateIn($this->actors->forUser($actorId), $teamId)) {
            throw new TrainingAccessDenied;
        }

        $details = TrainingDetailsFactory::from($input);

        return $this->transaction->run(function () use ($actorId, $teamId, $input, $details): int {
            $this->teams->find($teamId) ?? throw new WorkTeamNotFound;

            $pathId = $this->placement->place($input, $teamId, $details, null, $actorId);
            $draft = Training::draft($teamId, $details, $pathId, $this->clock->now());
            $id = $this->trainings->insert($draft, $actorId);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::TrainingDrafted,
                description: sprintf('Draft pelatihan %s dibuat.', TrainingAudit::label($draft)),
                actorId: $actorId,
                subjectType: 'training',
                subjectId: $id,
                context: TrainingAudit::snapshot($draft),
            ));

            return $id;
        });
    }
}
