<?php

declare(strict_types=1);

namespace App\Application\Sop\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class SopProvisionNotFound extends BusinessRuleViolation
{
    public function __construct(string $message = 'Ketentuan SOP tidak ditemukan.')
    {
        parent::__construct($message);
    }
}
