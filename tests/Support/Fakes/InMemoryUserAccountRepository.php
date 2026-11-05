<?php

namespace Tests\Support\Fakes;

use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Identity\NewUserAccount;
use App\Domain\Identity\Nip;
use DateTimeImmutable;

final class InMemoryUserAccountRepository implements UserAccountRepository
{
    /** @var array<int, array{account: NewUserAccount, passwordHash: string, passwordChangeRequired: bool, passwordChangedAt: ?DateTimeImmutable, lastLoginAt: ?DateTimeImmutable}> */
    public array $accounts = [];

    private int $nextId = 1;

    public function nipExists(Nip $nip): bool
    {
        foreach ($this->accounts as $record) {
            if ($record['account']->nip->equals($nip)) {
                return true;
            }
        }

        return false;
    }

    public function emailExists(string $email): bool
    {
        foreach ($this->accounts as $record) {
            if ($record['account']->email === $email) {
                return true;
            }
        }

        return false;
    }

    public function create(NewUserAccount $account): int
    {
        $id = $this->nextId++;
        $this->accounts[$id] = [
            'account' => $account,
            'passwordHash' => $account->passwordHash,
            'passwordChangeRequired' => $account->passwordChangeRequired,
            'passwordChangedAt' => null,
            'lastLoginAt' => null,
        ];

        return $id;
    }

    public function passwordHashFor(int $userId): ?string
    {
        return $this->accounts[$userId]['passwordHash'] ?? null;
    }

    public function updatePassword(int $userId, string $passwordHash, DateTimeImmutable $changedAt): void
    {
        if (! isset($this->accounts[$userId])) {
            return;
        }

        $this->accounts[$userId]['passwordHash'] = $passwordHash;
        $this->accounts[$userId]['passwordChangeRequired'] = false;
        $this->accounts[$userId]['passwordChangedAt'] = $changedAt;
    }

    public function recordLogin(int $userId, DateTimeImmutable $loggedInAt): void
    {
        if (! isset($this->accounts[$userId])) {
            return;
        }

        $this->accounts[$userId]['lastLoginAt'] = $loggedInAt;
    }
}
