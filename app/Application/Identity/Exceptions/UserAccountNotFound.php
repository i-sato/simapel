<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class UserAccountNotFound extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Akun tidak ditemukan.');
    }
}
