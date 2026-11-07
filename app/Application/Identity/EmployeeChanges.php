<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\EmployeeProfile;
use App\Domain\Identity\Nip;
use App\Domain\Identity\Role;

final readonly class EmployeeChanges
{
    public function __construct(
        public Nip $nip,
        public EmployeeProfile $profile,
        public Role $role,
        public bool $canBeAdmin,
    ) {}
}
