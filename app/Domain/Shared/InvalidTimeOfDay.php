<?php

declare(strict_types=1);

namespace App\Domain\Shared;

final class InvalidTimeOfDay extends BusinessRuleViolation
{
    public function __construct(string $field = 'time')
    {
        parent::__construct('Jam harus berformat JJ:MM (00:00–23:59).', $field);
    }
}
