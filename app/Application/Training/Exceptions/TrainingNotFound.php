<?php

declare(strict_types=1);

namespace App\Application\Training\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class TrainingNotFound extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Pelatihan tidak ditemukan.');
    }
}
