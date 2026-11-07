<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\Role;

final readonly class UpdateEmployeeInput
{
    public function __construct(
        public int $userId,
        public string $nip,
        public string $name,
        public ?string $email,
        public ?string $pangkatGolongan,
        public ?string $jabatan,
        public Role $role,
        public bool $canBeAdmin,
    ) {}
}
