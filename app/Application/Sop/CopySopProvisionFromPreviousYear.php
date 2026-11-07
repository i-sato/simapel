<?php

declare(strict_types=1);

namespace App\Application\Sop;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Sop\Contracts\SopProvisionRepository;
use App\Application\Sop\Exceptions\SopProvisionNotFound;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\SopProvision;
use App\Domain\Sop\SopProvisionResolver;
use DateTimeImmutable;

/**
 * Menyiapkan tahun anggaran baru: draf disalin dari ketentuan terakhir yang
 * berlaku di akhir tahun sebelumnya.
 */
final readonly class CopySopProvisionFromPreviousYear
{
    public function __construct(
        private SopProvisionRepository $provisions,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(WorkTeamCode $team, int $targetYear, int $actorId): int
    {
        return $this->transaction->run(function () use ($team, $targetYear, $actorId): int {
            $all = $this->provisions->forTeam($team, lock: true);
            $previousYearOnly = array_values(array_filter($all, fn (SopProvision $provision): bool => $provision->fiscalYear === $targetYear - 1));
            $source = SopProvisionResolver::resolve($previousYearOnly, new DateTimeImmutable(sprintf('%d-12-31', $targetYear - 1)))->provision
                ?? throw new SopProvisionNotFound(sprintf('Tidak ada ketentuan Berlaku untuk %s tahun %d yang dapat disalin.', $team->value, $targetYear - 1));

            $draft = $source->copyForYear($targetYear, $this->provisions->nextVersion($team, $targetYear));
            $id = $this->provisions->insert($draft, $actorId);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::SopProvisionDrafted,
                description: sprintf('Draf ketentuan SOP %s disalin dari %s.', SopProvisionAudit::label($draft), SopProvisionAudit::label($source)),
                actorId: $actorId,
                subjectType: 'sop_provision',
                subjectId: $id,
                context: SopProvisionAudit::snapshot($draft) + ['copied_from_id' => $source->id],
            ));

            return $id;
        });
    }
}
