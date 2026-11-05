<?php

declare(strict_types=1);

namespace App\Application\Audit\Contracts;

use App\Domain\Audit\AuditEvent;

interface AuditTrail
{
    public function record(AuditEvent $event): void;
}
