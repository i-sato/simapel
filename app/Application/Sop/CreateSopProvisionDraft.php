<?php

declare(strict_types=1);

namespace App\Application\Sop;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Sop\Contracts\SopProvisionRepository;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\SopProvision;

final readonly class CreateSopProvisionDraft
{
    public function __construct(
        private SopProvisionRepository $provisions,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(WorkTeamCode $team, int $fiscalYear, SopProvisionInput $input, int $actorId): int
    {
        $reference = SopProvisionFactory::reference($input);
        $effectiveFrom = SopProvisionFactory::effectiveFrom($input);
        $deadlines = SopProvisionFactory::deadlines($input);

        return $this->transaction->run(function () use ($team, $fiscalYear, $reference, $effectiveFrom, $deadlines, $input, $actorId): int {
            $this->provisions->forTeam($team, lock: true);

            $draft = SopProvision::draft($team, $fiscalYear, $this->provisions->nextVersion($team, $fiscalYear), $reference, $effectiveFrom, $deadlines, $input->note);
            $id = $this->provisions->insert($draft, $actorId);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::SopProvisionDrafted,
                description: sprintf('Draf ketentuan SOP %s dibuat.', SopProvisionAudit::label($draft)),
                actorId: $actorId,
                subjectType: 'sop_provision',
                subjectId: $id,
                context: SopProvisionAudit::snapshot($draft),
            ));

            return $id;
        });
    }
}
