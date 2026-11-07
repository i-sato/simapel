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

final readonly class DeleteSopProvisionDraft
{
    public function __construct(
        private SopProvisionRepository $provisions,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(int $id, int $actorId): void
    {
        $this->transaction->run(function () use ($id, $actorId): void {
            $current = $this->provisions->find($id) ?? throw new SopProvisionNotFound;
            $this->provisions->forTeam($current->team, lock: true);
            $current->assertDraft();

            if ($this->provisions->isReferenced($id)) {
                throw new SopProvisionLocked('Ketentuan sudah dipakai pelatihan sehingga tidak dapat dihapus.');
            }

            $this->provisions->delete($id);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::SopProvisionDeleted,
                description: sprintf('Draf ketentuan SOP %s dihapus.', SopProvisionAudit::label($current)),
                actorId: $actorId,
                subjectType: 'sop_provision',
                subjectId: $id,
                context: SopProvisionAudit::snapshot($current),
            ));
        });
    }
}
