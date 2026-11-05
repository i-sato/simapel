<?php

namespace Tests\Support\Fakes;

use App\Application\Identity\Contracts\PasswordHasher;

/**
 * Hasher deterministik untuk unit test (tanpa bcrypt agar cepat).
 */
final class FakePasswordHasher implements PasswordHasher
{
    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return 'hashed:'.$plainPassword;
    }

    public function verify(#[\SensitiveParameter] string $plainPassword, string $hash): bool
    {
        return $hash === 'hashed:'.$plainPassword;
    }
}
