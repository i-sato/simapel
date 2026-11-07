<?php

declare(strict_types=1);

namespace App\Application\Organization\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class WorkTeamNotFound extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Tim kerja tidak ditemukan.');
    }
}
