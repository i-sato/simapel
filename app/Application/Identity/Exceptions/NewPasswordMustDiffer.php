<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class NewPasswordMustDiffer extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Kata sandi baru harus berbeda dari kata sandi saat ini.', 'password');
    }
}
