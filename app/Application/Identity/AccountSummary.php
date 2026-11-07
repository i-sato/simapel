<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\Nip;
use App\Domain\Identity\Role;

final readonly class AccountSummary
{
    public function __construct(
        public int $id,
        public Nip $nip,
        public string $name,
        public Role $role,
        public bool $isActive,
        public bool $canBeAdmin = false,
    ) {}
}
