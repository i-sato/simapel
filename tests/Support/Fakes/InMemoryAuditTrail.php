<?php

namespace Tests\Support\Fakes;

use App\Application\Audit\Contracts\AuditTrail;
use App\Domain\Audit\AuditEvent;

final class InMemoryAuditTrail implements AuditTrail
{
    /** @var list<AuditEvent> */
    public array $events = [];

    public function record(AuditEvent $event): void
    {
        $this->events[] = $event;
    }
}
