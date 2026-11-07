<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Application\Administration\Contracts\AdministrativeDocumentRepository;
use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Administration\DocumentPermissions;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;

/**
 * Ketua tim (atau Super Admin) mendelegasikan dokumen ke Sekretaris tim kerja pelatihan.
 */
final readonly class DelegateDocument
{
    public function __construct(
        private DocumentAccess $access,
        private AdministrativeDocumentRepository $documents,
        private WorkTeamRepository $teams,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(int $actorId, int $documentId, ?int $assigneeId): void
    {
        $this->transaction->run(function () use ($actorId, $documentId, $assigneeId): void {
            [$document, $training, $actor] = $this->access->load($actorId, $documentId);

            if (! DocumentPermissions::canDelegate($actor, $training)) {
                DocumentAccess::deny();
            }

            $role = $assigneeId === null ? null : $this->teams->roleOf($training->teamId, $assigneeId);
            $delegated = $document->delegateTo($assigneeId, $role);

            if ($delegated->assigneeId === $document->assigneeId) {
                return;
            }

            $this->documents->save($delegated, $actorId);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::DocumentDelegated,
                description: sprintf('%s pelatihan "%s" %s.', $document->type->label(), $training->details->name, $assigneeId === null ? 'tidak lagi didelegasikan' : 'didelegasikan ke Sekretaris tim'),
                actorId: $actorId,
                subjectType: 'training',
                subjectId: $training->id,
                context: ['document' => $document->type->value, 'old_assignee_id' => $document->assigneeId, 'new_assignee_id' => $assigneeId],
            ));
        });
    }
}
