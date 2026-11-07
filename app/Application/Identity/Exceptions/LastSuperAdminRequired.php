<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class LastSuperAdminRequired extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Minimal harus ada satu Super Admin aktif.', 'role');
    }
}
