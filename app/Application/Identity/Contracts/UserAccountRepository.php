<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use App\Application\Identity\AccountSummary;
use App\Application\Identity\EmployeeChanges;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\Exceptions\NipAlreadyRegistered;
use App\Application\Identity\NewUserAccount;
use App\Domain\Identity\Nip;
use DateTimeImmutable;

interface UserAccountRepository
{
    public function nipExists(Nip $nip, ?int $exceptUserId = null): bool;

    public function emailExists(string $email, ?int $exceptUserId = null): bool;

    /**
     * @return int ID akun baru
     *
     * @throws NipAlreadyRegistered|EmailAlreadyRegistered bila kalah balapan dengan penyimpanan lain
     */
    public function create(NewUserAccount $account): int;

    public function find(int $userId): ?AccountSummary;

    /**
     * @throws NipAlreadyRegistered|EmailAlreadyRegistered
     */
    public function update(int $userId, EmployeeChanges $changes): void;

    public function setActive(int $userId, bool $active): void;

    /**
     * Mengganti kata sandi dengan kata sandi sementara yang wajib diganti saat login.
     */
    public function setTemporaryPassword(int $userId, string $passwordHash): void;

    /**
     * Jumlah Super Admin aktif selain akun tertentu. Implementasi wajib mengunci
     * baris terkait agar dua perubahan bersamaan tidak menghapus Super Admin terakhir.
     */
    public function countOtherActiveSuperAdmins(int $exceptUserId): int;

    public function passwordHashFor(int $userId): ?string;

    public function updatePassword(int $userId, string $passwordHash, DateTimeImmutable $changedAt): void;

    public function recordLogin(int $userId, DateTimeImmutable $loggedInAt): void;
}
