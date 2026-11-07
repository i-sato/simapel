<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\Contracts\SessionTerminator;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Identity\Exceptions\CannotChangeOwnAccess;
use App\Application\Identity\Exceptions\LastSuperAdminRequired;
use App\Application\Identity\Exceptions\UserAccountNotFound;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Role;

/**
 * Mengaktifkan atau menonaktifkan akun. Menonaktifkan langsung mengakhiri
 * semua sesi akun tersebut.
 */
final readonly class SetEmployeeActive
{
    public function __construct(
        private UserAccountRepository $users,
        private SessionTerminator $sessions,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(int $userId, bool $active, int $actorId): void
    {
        $this->transaction->run(function () use ($userId, $active, $actorId): void {
            $account = $this->users->find($userId) ?? throw new UserAccountNotFound;

            if (! $active && $account->id === $actorId) {
                throw new CannotChangeOwnAccess;
            }

            if (! $active && $account->role === Role::SuperAdmin && $this->users->countOtherActiveSuperAdmins($account->id) === 0) {
                throw new LastSuperAdminRequired;
            }

            $this->users->setActive($account->id, $active);

            $this->audit->record(new AuditEvent(
                type: $active ? AuditEventType::EmployeeActivated : AuditEventType::EmployeeDeactivated,
                description: sprintf('Akun %s %s.', $account->nip->masked(), $active ? 'diaktifkan' : 'dinonaktifkan'),
                actorId: $actorId,
                subjectType: 'user',
                subjectId: $account->id,
                context: ['nip_masked' => $account->nip->masked()],
            ));
        });

        if (! $active) {
            $this->sessions->terminateAllSessions($userId);
        }
    }
}
