<?php

declare(strict_types=1);

namespace App\Application\Identity;

final readonly class ChangeOwnPasswordInput
{
    public function __construct(
        public int $userId,
        #[\SensitiveParameter] public string $currentPassword,
        #[\SensitiveParameter] public string $newPassword,
        public ?string $currentSessionId = null,
    ) {}
}
