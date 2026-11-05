<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\SessionTerminator;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Identity\Exceptions\CurrentPasswordIncorrect;
use App\Application\Identity\Exceptions\NewPasswordMustDiffer;
use App\Application\Identity\Exceptions\UserAccountNotFound;
use App\Application\Shared\Contracts\Clock;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\PasswordPolicy;

/**
 * Pengguna mengganti kata sandinya sendiri. Sesi lain milik akun tersebut
 * diakhiri agar kata sandi lama yang mungkin bocor tidak lagi berguna.
 */
final readonly class ChangeOwnPassword
{
    public function __construct(
        private UserAccountRepository $users,
        private PasswordHasher $hasher,
        private PasswordPolicy $passwordPolicy,
        private SessionTerminator $sessions,
        private AuditTrail $audit,
        private Transaction $transaction,
        private Clock $clock,
    ) {}

    public function handle(ChangeOwnPasswordInput $input): void
    {
        $currentHash = $this->users->passwordHashFor($input->userId) ?? throw new UserAccountNotFound;

        if (! $this->hasher->verify($input->currentPassword, $currentHash)) {
            throw new CurrentPasswordIncorrect;
        }

        if ($this->hasher->verify($input->newPassword, $currentHash)) {
            throw new NewPasswordMustDiffer;
        }

        $this->passwordPolicy->assertSatisfiedBy($input->newPassword);

        $this->transaction->run(function () use ($input): void {
            $this->users->updatePassword($input->userId, $this->hasher->hash($input->newPassword), $this->clock->now());

            $this->audit->record(new AuditEvent(
                type: AuditEventType::PasswordChanged,
                description: 'Kata sandi akun diganti oleh pemilik akun.',
                actorId: $input->userId,
                subjectType: 'user',
                subjectId: $input->userId,
            ));
        });

        $this->sessions->terminateOtherSessions($input->userId, $input->currentSessionId);
    }
}
