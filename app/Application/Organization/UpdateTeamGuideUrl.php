<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Organization\Exceptions\WorkTeamNotFound;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Shared\HttpUrl;

/**
 * Link panduan pendaftaran pelatihan milik tim kerja (keputusan K7).
 */
final readonly class UpdateTeamGuideUrl
{
    public function __construct(
        private WorkTeamRepository $teams,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(int $teamId, ?string $url, int $actorId): void
    {
        $new = HttpUrl::fromNullable($url, 'registration_guide_url')?->value;

        $this->transaction->run(function () use ($teamId, $new, $actorId): void {
            $team = $this->teams->find($teamId) ?? throw new WorkTeamNotFound;

            if ($team->registrationGuideUrl === $new) {
                return;
            }

            $this->teams->setRegistrationGuideUrl($team->id, $new);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::TeamGuideUpdated,
                description: sprintf('Link panduan pendaftaran %s diubah.', $team->label()),
                actorId: $actorId,
                subjectType: 'work_team',
                subjectId: $team->id,
                context: ['old_value' => $team->registrationGuideUrl, 'new_value' => $new],
            ));
        });
    }
}
