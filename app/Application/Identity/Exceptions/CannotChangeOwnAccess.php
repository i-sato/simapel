<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class CannotChangeOwnAccess extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Anda tidak dapat menonaktifkan atau menurunkan peran akun Anda sendiri.', 'role');
    }
}
