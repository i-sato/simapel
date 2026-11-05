<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class EmailAlreadyRegistered extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Email sudah terdaftar pada akun lain.', 'email');
    }
}
