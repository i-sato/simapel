<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Identity\Exceptions\CannotChangeOwnAccess;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\Exceptions\LastSuperAdminRequired;
use App\Application\Identity\Exceptions\NipAlreadyRegistered;
use App\Application\Identity\Exceptions\UserAccountNotFound;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\EmployeeProfile;
use App\Domain\Identity\Nip;
use App\Domain\Identity\Role;

/**
 * Super Admin mengubah data pegawai, peran global, dan kapabilitas Admin.
 */
final readonly class UpdateEmployee
{
    public function __construct(
        private UserAccountRepository $users,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(UpdateEmployeeInput $input, int $actorId): void
    {
        $nip = Nip::fromString($input->nip);
        $profile = EmployeeProfile::fromInput($input->name, $input->email, $input->pangkatGolongan, $input->jabatan);

        $this->transaction->run(function () use ($input, $actorId, $nip, $profile): void {
            $current = $this->users->find($input->userId) ?? throw new UserAccountNotFound;
            $demotesSuperAdmin = $current->role === Role::SuperAdmin && $input->role !== Role::SuperAdmin;

            if ($demotesSuperAdmin && $current->id === $actorId) {
                throw new CannotChangeOwnAccess;
            }

            if ($demotesSuperAdmin && $current->isActive && $this->users->countOtherActiveSuperAdmins($current->id) === 0) {
                throw new LastSuperAdminRequired;
            }

            if ($this->users->nipExists($nip, $current->id)) {
                throw new NipAlreadyRegistered;
            }

            if ($profile->email !== null && $this->users->emailExists($profile->email, $current->id)) {
                throw new EmailAlreadyRegistered;
            }

            $this->users->update($current->id, new EmployeeChanges($nip, $profile, $input->role, $input->canBeAdmin));

            $this->audit->record(new AuditEvent(
                type: AuditEventType::EmployeeUpdated,
                description: sprintf('Data pegawai %s diubah.', $nip->masked()),
                actorId: $actorId,
                subjectType: 'user',
                subjectId: $current->id,
                context: [
                    'nip_masked' => $nip->masked(),
                    'nip_changed' => ! $nip->equals($current->nip),
                    'role' => $input->role->value,
                    'can_be_admin' => $input->canBeAdmin,
                ],
            ));
        });
    }
}
