<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\EmployeeProfile;
use App\Domain\Identity\Nip;
use App\Domain\Identity\Role;
use App\Domain\Shared\BusinessRuleViolation;

/**
 * Impor daftar pegawai: validasi seluruh baris terlebih dahulu, lalu simpan
 * semua atau tidak sama sekali. NIP yang sudah terdaftar tidak ditimpa.
 *
 * Akun hasil impor memakai kata sandi acak yang tidak diketahui siapa pun dan
 * wajib diganti; pegawai baru dapat login setelah Super Admin menetapkan kata
 * sandi sementara lewat ResetEmployeePassword.
 */
final readonly class ImportEmployees
{
    public const MAX_ROWS = 1000;

    public function __construct(
        private UserAccountRepository $users,
        private PasswordHasher $hasher,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    /**
     * @param  list<EmployeeImportRow>  $rows
     */
    public function handle(array $rows, int $actorId): ImportResult
    {
        if ($rows === []) {
            return ImportResult::failed([new ImportRowError(0, 'Berkas tidak berisi data pegawai.')]);
        }

        if (count($rows) > self::MAX_ROWS) {
            return ImportResult::failed([new ImportRowError(0, sprintf('Maksimal %d baris per impor.', self::MAX_ROWS))]);
        }

        $accounts = [];
        $errors = [];
        $seenNips = [];
        $seenEmails = [];

        foreach ($rows as $row) {
            try {
                $nip = Nip::fromString($row->nip);
                $profile = EmployeeProfile::fromInput($row->name, $row->email, $row->pangkatGolongan, $row->jabatan);
            } catch (BusinessRuleViolation $violation) {
                $errors[] = new ImportRowError($row->line, $violation->getMessage());

                continue;
            }

            $rowErrors = [];

            if (isset($seenNips[$nip->value])) {
                $rowErrors[] = sprintf('NIP sama dengan baris %d.', $seenNips[$nip->value]);
            } elseif ($this->users->nipExists($nip)) {
                $rowErrors[] = 'NIP sudah terdaftar.';
            }

            if ($profile->email !== null) {
                if (isset($seenEmails[$profile->email])) {
                    $rowErrors[] = sprintf('Email sama dengan baris %d.', $seenEmails[$profile->email]);
                } elseif ($this->users->emailExists($profile->email)) {
                    $rowErrors[] = 'Email sudah terdaftar.';
                }

                $seenEmails[$profile->email] ??= $row->line;
            }

            $seenNips[$nip->value] ??= $row->line;

            if ($rowErrors !== []) {
                $errors[] = new ImportRowError($row->line, implode(' ', $rowErrors));

                continue;
            }

            $accounts[] = new NewUserAccount(
                nip: $nip,
                profile: $profile,
                role: Role::Pegawai,
                canBeAdmin: false,
                passwordHash: $this->hasher->hash(bin2hex(random_bytes(32))),
                passwordChangeRequired: true,
            );
        }

        if ($errors !== []) {
            return ImportResult::failed($errors);
        }

        $this->transaction->run(function () use ($accounts, $actorId): void {
            foreach ($accounts as $account) {
                $userId = $this->users->create($account);

                $this->audit->record(new AuditEvent(
                    type: AuditEventType::UserCreated,
                    description: sprintf('Akun %s dibuat lewat impor.', $account->nip->masked()),
                    actorId: $actorId,
                    subjectType: 'user',
                    subjectId: $userId,
                    context: ['nip_masked' => $account->nip->masked(), 'role' => $account->role->value],
                ));
            }

            $this->audit->record(new AuditEvent(
                type: AuditEventType::EmployeesImported,
                description: sprintf('%d pegawai diimpor.', count($accounts)),
                actorId: $actorId,
                context: ['count' => count($accounts)],
            ));
        });

        return ImportResult::succeeded(count($accounts));
    }
}
