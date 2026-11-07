<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Shared\BusinessRuleViolation;

final class NotATeamMember extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Pegawai tersebut bukan anggota tim ini.', 'employee_id');
    }
}
