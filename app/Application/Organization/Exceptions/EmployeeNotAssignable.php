<?php

declare(strict_types=1);

namespace App\Application\Organization\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class EmployeeNotAssignable extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Pegawai tidak ditemukan atau akunnya tidak aktif.', 'employee_id');
    }
}
