<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use App\Domain\Shared\BusinessRuleViolation;

final class SingleSeatRoleTaken extends BusinessRuleViolation
{
    public function __construct(TeamRole $role)
    {
        parent::__construct(sprintf(
            'Tim ini sudah memiliki %1$s. Ubah dulu %1$s yang sekarang menjadi Anggota atau keluarkan dari tim.',
            $role->label(),
        ), 'role');
    }
}
