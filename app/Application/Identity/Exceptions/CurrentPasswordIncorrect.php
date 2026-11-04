<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class CurrentPasswordIncorrect extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Kata sandi saat ini tidak sesuai.', 'current_password');
    }
}
