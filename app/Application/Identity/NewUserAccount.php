<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\Nip;
use App\Domain\Identity\Role;

/**
 * Data akun yang sudah tervalidasi dan siap disimpan.
 */
final readonly class NewUserAccount
{
    public function __construct(
        public Nip $nip,
        public string $name,
        public ?string $email,
        public Role $role,
        public string $passwordHash,
        public bool $passwordChangeRequired,
    ) {}
}
