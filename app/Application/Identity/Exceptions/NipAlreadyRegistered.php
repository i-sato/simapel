<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class NipAlreadyRegistered extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('NIP sudah terdaftar pada akun lain.', 'nip');
    }
}
