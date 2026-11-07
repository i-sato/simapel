<?php

declare(strict_types=1);

namespace App\Application\Sop;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Sop\Contracts\SopProvisionRepository;
use App\Application\Sop\Exceptions\SopProvisionNotFound;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Sop\SopProvision;
use App\Domain\Sop\SopProvisionHistory;

/**
 * Super Admin memberlakukan draf. Versi Berlaku sebelumnya di tahun yang sama
 * menjadi Diganti mulai tanggal berlaku versi baru.
 */
final readonly class ActivateSopProvision
{
    public function __construct(
        private SopProvisionRepository $provisions,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(int $id, int $actorId): void
    {
        $this->transaction->run(function () use ($id, $actorId): void {
            $draft = $this->provisions->find($id) ?? throw new SopProvisionNotFound;
            $sameYear = array_values(array_filter(
                $this->provisions->forTeam($draft->team, lock: true),
                fn (SopProvision $provision): bool => $provision->fiscalYear === $draft->fiscalYear,
            ));

            foreach (SopProvisionHistory::activate($draft, $sameYear) as $index => $changed) {
                $this->provisions->save($changed, $actorId);

                $this->audit->record(new AuditEvent(
                    type: $index === 0 ? AuditEventType::SopProvisionActivated : AuditEventType::SopProvisionSuperseded,
                    description: $index === 0
                        ? sprintf('Ketentuan SOP %s diberlakukan mulai %s.', SopProvisionAudit::label($changed), $changed->effectiveFrom->format('d-m-Y'))
                        : sprintf('Ketentuan SOP %s diganti mulai %s.', SopProvisionAudit::label($changed), $changed->supersededFrom?->format('d-m-Y')),
                    actorId: $actorId,
                    subjectType: 'sop_provision',
                    subjectId: $changed->id,
                    context: SopProvisionAudit::snapshot($changed),
                ));
            }
        });
    }
}
