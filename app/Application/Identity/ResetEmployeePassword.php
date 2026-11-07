<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\SessionTerminator;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Identity\Exceptions\UserAccountNotFound;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\PasswordPolicy;

/**
 * Super Admin menetapkan kata sandi sementara. Pegawai wajib menggantinya saat
 * login berikutnya, dan semua sesi lama diakhiri.
 */
final readonly class ResetEmployeePassword
{
    public function __construct(
        private UserAccountRepository $users,
        private PasswordHasher $hasher,
        private PasswordPolicy $passwordPolicy,
        private SessionTerminator $sessions,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(int $userId, #[\SensitiveParameter] string $temporaryPassword, int $actorId): void
    {
        $this->passwordPolicy->assertSatisfiedBy($temporaryPassword);

        $this->transaction->run(function () use ($userId, $temporaryPassword, $actorId): void {
            $account = $this->users->find($userId) ?? throw new UserAccountNotFound;

            $this->users->setTemporaryPassword($account->id, $this->hasher->hash($temporaryPassword));

            $this->audit->record(new AuditEvent(
                type: AuditEventType::EmployeePasswordReset,
                description: sprintf('Kata sandi akun %s direset; wajib diganti saat login.', $account->nip->masked()),
                actorId: $actorId,
                subjectType: 'user',
                subjectId: $account->id,
                context: ['nip_masked' => $account->nip->masked()],
            ));
        });

        $this->sessions->terminateAllSessions($userId);
    }
}
