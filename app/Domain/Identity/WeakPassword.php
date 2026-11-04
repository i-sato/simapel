<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Domain\Shared\BusinessRuleViolation;

final class WeakPassword extends BusinessRuleViolation
{
    public function __construct(string $message)
    {
        parent::__construct($message, 'password');
    }
}
