<?php

namespace Tests\Support\Fakes;

use App\Application\Identity\AccountSummary;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Identity\EmployeeChanges;
use App\Application\Identity\NewUserAccount;
use App\Domain\Identity\EmployeeProfile;
use App\Domain\Identity\Nip;
use App\Domain\Identity\Role;
use DateTimeImmutable;

/**
 * @phpstan-type Record array{nip: Nip, profile: EmployeeProfile, role: Role, canBeAdmin: bool, isActive: bool, passwordHash: string, passwordChangeRequired: bool, passwordChangedAt: ?DateTimeImmutable, lastLoginAt: ?DateTimeImmutable}
 */
final class InMemoryUserAccountRepository implements UserAccountRepository
{
    /** @var array<int, Record> */
    public array $accounts = [];

    private int $nextId = 1;

    public function nipExists(Nip $nip, ?int $exceptUserId = null): bool
    {
        foreach ($this->accounts as $id => $record) {
            if ($id !== $exceptUserId && $record['nip']->equals($nip)) {
                return true;
            }
        }

        return false;
    }

    public function emailExists(string $email, ?int $exceptUserId = null): bool
    {
        foreach ($this->accounts as $id => $record) {
            if ($id !== $exceptUserId && $record['profile']->email === $email) {
                return true;
            }
        }

        return false;
    }

    public function create(NewUserAccount $account): int
    {
        $id = $this->nextId++;
        $this->accounts[$id] = [
            'nip' => $account->nip,
            'profile' => $account->profile,
            'role' => $account->role,
            'canBeAdmin' => $account->canBeAdmin,
            'isActive' => true,
            'passwordHash' => $account->passwordHash,
            'passwordChangeRequired' => $account->passwordChangeRequired,
            'passwordChangedAt' => null,
            'lastLoginAt' => null,
        ];

        return $id;
    }

    public function find(int $userId): ?AccountSummary
    {
        if (! isset($this->accounts[$userId])) {
            return null;
        }

        $record = $this->accounts[$userId];

        return new AccountSummary($userId, $record['nip'], $record['profile']->name, $record['role'], $record['isActive']);
    }

    public function update(int $userId, EmployeeChanges $changes): void
    {
        if (! isset($this->accounts[$userId])) {
            return;
        }

        $this->accounts[$userId]['nip'] = $changes->nip;
        $this->accounts[$userId]['profile'] = $changes->profile;
        $this->accounts[$userId]['role'] = $changes->role;
        $this->accounts[$userId]['canBeAdmin'] = $changes->canBeAdmin;
    }

    public function setActive(int $userId, bool $active): void
    {
        if (isset($this->accounts[$userId])) {
            $this->accounts[$userId]['isActive'] = $active;
        }
    }

    public function setTemporaryPassword(int $userId, string $passwordHash): void
    {
        if (isset($this->accounts[$userId])) {
            $this->accounts[$userId]['passwordHash'] = $passwordHash;
            $this->accounts[$userId]['passwordChangeRequired'] = true;
        }
    }

    public function countOtherActiveSuperAdmins(int $exceptUserId): int
    {
        $count = 0;

        foreach ($this->accounts as $id => $record) {
            if ($id !== $exceptUserId && $record['isActive'] && $record['role'] === Role::SuperAdmin) {
                $count++;
            }
        }

        return $count;
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
        if (isset($this->accounts[$userId])) {
            $this->accounts[$userId]['lastLoginAt'] = $loggedInAt;
        }
    }

    /**
     * Membuat akun langsung untuk menyiapkan skenario uji.
     */
    public function seed(string $nip, Role $role = Role::Pegawai, bool $active = true, string $name = 'Pegawai Uji', ?string $email = null): int
    {
        $id = $this->create(new NewUserAccount(
            Nip::fromString($nip),
            EmployeeProfile::fromInput($name, $email, null, null),
            $role,
            false,
            'hashed:Sandi-Lama-2026!',
            false,
        ));
        $this->accounts[$id]['isActive'] = $active;

        return $id;
    }
}
