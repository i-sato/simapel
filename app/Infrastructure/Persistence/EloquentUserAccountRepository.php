<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Identity\AccountSummary;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Identity\EmployeeChanges;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\Exceptions\NipAlreadyRegistered;
use App\Application\Identity\Exceptions\UserAccountNotFound;
use App\Application\Identity\NewUserAccount;
use App\Domain\Identity\Nip;
use App\Domain\Identity\Role;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentUserAccountRepository implements UserAccountRepository
{
    public function nipExists(Nip $nip, ?int $exceptUserId = null): bool
    {
        return User::query()
            ->where('nip', $nip->value)
            ->when($exceptUserId !== null, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->exists();
    }

    public function emailExists(string $email, ?int $exceptUserId = null): bool
    {
        return User::query()
            ->where('email', $email)
            ->when($exceptUserId !== null, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->exists();
    }

    public function create(NewUserAccount $account): int
    {
        try {
            $user = User::query()->create([
                'nip' => $account->nip->value,
                'name' => $account->profile->name,
                'email' => $account->profile->email,
                'pangkat_golongan' => $account->profile->pangkatGolongan,
                'jabatan' => $account->profile->jabatan,
                'role' => $account->role,
                'can_be_admin' => $account->canBeAdmin,
                'is_active' => true,
                'password' => $account->passwordHash,
                'password_change_required' => $account->passwordChangeRequired,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw $this->nipExists($account->nip) ? new NipAlreadyRegistered : new EmailAlreadyRegistered;
        }

        return (int) $user->getKey();
    }

    public function find(int $userId): ?AccountSummary
    {
        $user = User::query()->find($userId);

        return $user === null ? null : new AccountSummary(
            id: $user->id,
            nip: $user->nipValue(),
            name: $user->name,
            role: $user->role,
            isActive: $user->is_active,
        );
    }

    public function update(int $userId, EmployeeChanges $changes): void
    {
        $user = User::query()->find($userId) ?? throw new UserAccountNotFound;

        try {
            $user->forceFill([
                'nip' => $changes->nip->value,
                'name' => $changes->profile->name,
                'email' => $changes->profile->email,
                'pangkat_golongan' => $changes->profile->pangkatGolongan,
                'jabatan' => $changes->profile->jabatan,
                'role' => $changes->role,
                'can_be_admin' => $changes->canBeAdmin,
            ])->save();
        } catch (UniqueConstraintViolationException) {
            throw $this->nipExists($changes->nip, $userId) ? new NipAlreadyRegistered : new EmailAlreadyRegistered;
        }
    }

    public function setActive(int $userId, bool $active): void
    {
        $user = User::query()->find($userId) ?? throw new UserAccountNotFound;
        $user->forceFill(['is_active' => $active])->save();
    }

    public function setTemporaryPassword(int $userId, string $passwordHash): void
    {
        $user = User::query()->find($userId) ?? throw new UserAccountNotFound;

        $user->forceFill([
            'password' => $passwordHash,
            'password_change_required' => true,
        ])->save();
    }

    public function countOtherActiveSuperAdmins(int $exceptUserId): int
    {
        return User::query()
            ->where('role', Role::SuperAdmin)
            ->where('is_active', true)
            ->whereKeyNot($exceptUserId)
            ->lockForUpdate()
            ->count();
    }

    public function passwordHashFor(int $userId): ?string
    {
        $hash = User::query()->whereKey($userId)->value('password');

        return is_string($hash) ? $hash : null;
    }

    public function updatePassword(int $userId, string $passwordHash, DateTimeImmutable $changedAt): void
    {
        $user = User::query()->find($userId) ?? throw new UserAccountNotFound;

        $user->forceFill([
            'password' => $passwordHash,
            'password_change_required' => false,
            'password_changed_at' => $changedAt,
        ])->save();
    }

    public function recordLogin(int $userId, DateTimeImmutable $loggedInAt): void
    {
        // toBase(): stempel login tidak dianggap perubahan profil (updated_at tetap).
        User::query()->whereKey($userId)->toBase()->update(['last_login_at' => $loggedInAt]);
    }
}
