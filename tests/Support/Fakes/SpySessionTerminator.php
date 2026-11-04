<?php

namespace Tests\Support\Fakes;

use App\Application\Identity\Contracts\SessionTerminator;

final class SpySessionTerminator implements SessionTerminator
{
    /** @var list<array{userId: int, keep: ?string}> */
    public array $otherSessionCalls = [];

    /** @var list<int> */
    public array $allSessionCalls = [];

    public function terminateOtherSessions(int $userId, ?string $keepSessionId): int
    {
        $this->otherSessionCalls[] = ['userId' => $userId, 'keep' => $keepSessionId];

        return 0;
    }

    public function terminateAllSessions(int $userId): int
    {
        $this->allSessionCalls[] = $userId;

        return 0;
    }
}
