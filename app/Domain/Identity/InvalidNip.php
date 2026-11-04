<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Domain\Shared\BusinessRuleViolation;

final class InvalidNip extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('NIP harus terdiri dari tepat 18 digit angka.', 'nip');
    }
}
