<?php

declare(strict_types=1);

namespace App\Application\Partner;

use App\Application\Audit\Contracts\AuditTrail;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Partner\Partner;

final class PartnerAudit
{
    public static function created(AuditTrail $audit, int $actorId, int $id, Partner $partner): void
    {
        $audit->record(new AuditEvent(
            type: AuditEventType::PartnerCreated,
            description: sprintf('Mitra "%s" didaftarkan.', $partner->name),
            actorId: $actorId,
            subjectType: 'partner',
            subjectId: $id,
            context: ['name' => $partner->name, 'region' => $partner->region->value],
        ));
    }
}
