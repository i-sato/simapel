<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Identity\Contracts\PasswordHasher;
use Illuminate\Contracts\Hashing\Hasher;

final readonly class LaravelPasswordHasher implements PasswordHasher
{
    public function __construct(private Hasher $hasher) {}

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return $this->hasher->make($plainPassword);
    }

    public function verify(#[\SensitiveParameter] string $plainPassword, string $hash): bool
    {
        return $this->hasher->check($plainPassword, $hash);
    }
}
