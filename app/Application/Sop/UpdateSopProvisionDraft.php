<?php

declare(strict_types=1);

namespace App\Application\Sop;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Sop\Contracts\SopProvisionRepository;
use App\Application\Sop\Exceptions\SopProvisionNotFound;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Sop\SopProvisionLocked;

final readonly class UpdateSopProvisionDraft
{
    public function __construct(
        private SopProvisionRepository $provisions,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(int $id, SopProvisionInput $input, int $actorId): void
    {
        $reference = SopProvisionFactory::reference($input);
        $effectiveFrom = SopProvisionFactory::effectiveFrom($input);
        $deadlines = SopProvisionFactory::deadlines($input);

        $this->transaction->run(function () use ($id, $reference, $effectiveFrom, $deadlines, $input, $actorId): void {
            $current = $this->provisions->find($id) ?? throw new SopProvisionNotFound;
            $this->provisions->forTeam($current->team, lock: true);

            if ($this->provisions->isReferenced($id)) {
                throw new SopProvisionLocked('Ketentuan sudah dipakai pelatihan sehingga tidak dapat diubah.');
            }

            $revised = $current->revise($reference, $effectiveFrom, $deadlines, $input->note);
            $this->provisions->save($revised, $actorId);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::SopProvisionUpdated,
                description: sprintf('Draf ketentuan SOP %s diubah.', SopProvisionAudit::label($revised)),
                actorId: $actorId,
                subjectType: 'sop_provision',
                subjectId: $id,
                context: SopProvisionAudit::snapshot($current, 'old_') + SopProvisionAudit::snapshot($revised, 'new_'),
            ));
        });
    }
}
