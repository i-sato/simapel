<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\Exceptions\NipAlreadyRegistered;
use App\Application\Identity\Exceptions\UserAccountNotFound;
use App\Application\Identity\NewUserAccount;
use App\Domain\Identity\Nip;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentUserAccountRepository implements UserAccountRepository
{
    public function nipExists(Nip $nip): bool
    {
        return User::query()->where('nip', $nip->value)->exists();
    }

    public function emailExists(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    public function create(NewUserAccount $account): int
    {
        try {
            $user = User::query()->create([
                'nip' => $account->nip->value,
                'name' => $account->name,
                'email' => $account->email,
                'role' => $account->role,
                'is_active' => true,
                'password' => $account->passwordHash,
                'password_change_required' => $account->passwordChangeRequired,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw $this->nipExists($account->nip) ? new NipAlreadyRegistered : new EmailAlreadyRegistered;
        }

        return (int) $user->getKey();
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
