<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\Role;

final readonly class CreateUserAccountInput
{
    public function __construct(
        public string $nip,
        public string $name,
        public ?string $email,
        public Role $role,
        #[\SensitiveParameter] public string $password,
        public bool $passwordChangeRequired = true,
    ) {}
}
