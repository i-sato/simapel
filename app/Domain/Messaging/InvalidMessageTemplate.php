<?php

declare(strict_types=1);

namespace App\Domain\Messaging;

use App\Domain\Shared\BusinessRuleViolation;

final class InvalidMessageTemplate extends BusinessRuleViolation
{
    public function __construct(string $message)
    {
        parent::__construct($message, 'body');
    }
}
