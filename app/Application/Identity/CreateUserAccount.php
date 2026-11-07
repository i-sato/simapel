<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\Exceptions\NipAlreadyRegistered;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\EmployeeProfile;
use App\Domain\Identity\Nip;
use App\Domain\Identity\PasswordPolicy;

final readonly class CreateUserAccount
{
    public function __construct(
        private UserAccountRepository $users,
        private PasswordHasher $hasher,
        private PasswordPolicy $passwordPolicy,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    /**
     * @return int ID akun baru
     */
    public function handle(CreateUserAccountInput $input, ?int $actorId = null): int
    {
        $nip = Nip::fromString($input->nip);
        $profile = EmployeeProfile::fromInput($input->name, $input->email, $input->pangkatGolongan, $input->jabatan);

        $this->passwordPolicy->assertSatisfiedBy($input->password);

        if ($this->users->nipExists($nip)) {
            throw new NipAlreadyRegistered;
        }

        if ($profile->email !== null && $this->users->emailExists($profile->email)) {
            throw new EmailAlreadyRegistered;
        }

        $account = new NewUserAccount(
            nip: $nip,
            profile: $profile,
            role: $input->role,
            canBeAdmin: $input->canBeAdmin,
            passwordHash: $this->hasher->hash($input->password),
            passwordChangeRequired: $input->passwordChangeRequired,
        );

        return $this->transaction->run(function () use ($account, $actorId): int {
            $userId = $this->users->create($account);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::UserCreated,
                description: sprintf('Akun %s dibuat dengan peran %s.', $account->nip->masked(), $account->role->label()),
                actorId: $actorId,
                subjectType: 'user',
                subjectId: $userId,
                context: ['nip_masked' => $account->nip->masked(), 'role' => $account->role->value],
            ));

            return $userId;
        });
    }
}
