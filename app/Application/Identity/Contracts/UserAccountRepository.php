<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\Exceptions\NipAlreadyRegistered;
use App\Application\Identity\NewUserAccount;
use App\Domain\Identity\Nip;
use DateTimeImmutable;

interface UserAccountRepository
{
    public function nipExists(Nip $nip): bool;

    public function emailExists(string $email): bool;

    /**
     * @return int ID akun baru
     *
     * @throws NipAlreadyRegistered|EmailAlreadyRegistered bila kalah balapan dengan penyimpanan lain
     */
    public function create(NewUserAccount $account): int;

    public function passwordHashFor(int $userId): ?string;

    public function updatePassword(int $userId, string $passwordHash, DateTimeImmutable $changedAt): void;

    public function recordLogin(int $userId, DateTimeImmutable $loggedInAt): void;
}
