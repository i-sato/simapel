<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

interface PasswordHasher
{
    public function hash(#[\SensitiveParameter] string $plainPassword): string;

    public function verify(#[\SensitiveParameter] string $plainPassword, string $hash): bool;
}
