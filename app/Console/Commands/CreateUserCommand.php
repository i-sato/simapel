<?php

namespace App\Console\Commands;

use App\Application\Identity\CreateUserAccount;
use App\Application\Identity\CreateUserAccountInput;
use App\Domain\Identity\Nip;
use App\Domain\Identity\PasswordPolicy;
use App\Domain\Identity\Role;
use App\Domain\Shared\BusinessRuleViolation;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Membuat akun lewat terminal, termasuk Super Admin pertama.
 * Kata sandi selalu ditanyakan secara tersembunyi, tidak pernah lewat opsi CLI,
 * agar tidak tersimpan di riwayat shell.
 */
final class CreateUserCommand extends Command
{
    protected $signature = 'simapel:user:create
        {--nip= : NIP 18 digit}
        {--name= : Nama lengkap}
        {--email= : Email (opsional)}
        {--role= : super_admin_regional | admin_regional | pegawai}
        {--permanent-password : Tidak mewajibkan ganti kata sandi saat login pertama}';

    protected $description = 'Membuat akun pengguna SIMAPEL (kata sandi diminta secara tersembunyi)';

    public function handle(CreateUserAccount $createUserAccount, PasswordPolicy $policy): int
    {
        $nip = $this->stringOption('nip') ?? text(
            label: 'NIP (18 digit)',
            required: true,
            validate: fn (string $value): ?string => Nip::isValid(trim($value)) ? null : 'NIP harus terdiri dari tepat 18 digit angka.',
        );

        $name = $this->stringOption('name') ?? text(label: 'Nama lengkap', required: true);

        $email = $this->stringOption('email') ?? text(label: 'Email (opsional)', hint: 'Kosongkan bila tidak ada.');

        $role = $this->stringOption('role') ?? select(
            label: 'Peran',
            options: Role::options(),
            default: Role::Pegawai->value,
        );

        if (Role::tryFrom((string) $role) === null) {
            $this->components->error('Peran tidak dikenal. Pilihan: '.implode(', ', array_keys(Role::options())).'.');

            return self::FAILURE;
        }

        $plainPassword = password(
            label: 'Kata sandi awal',
            required: true,
            validate: fn (string $value): ?string => $policy->violations($value)[0] ?? null,
            hint: implode('; ', $policy->summary()),
        );

        if (! hash_equals($plainPassword, password(label: 'Ulangi kata sandi', required: true))) {
            $this->components->error('Konfirmasi kata sandi tidak sama.');

            return self::FAILURE;
        }

        $mustChange = $this->option('permanent-password')
            ? false
            : confirm(label: 'Wajibkan ganti kata sandi saat login pertama?', default: true);

        try {
            $userId = $createUserAccount->handle(new CreateUserAccountInput(
                nip: (string) $nip,
                name: (string) $name,
                email: $email === '' ? null : (string) $email,
                role: Role::from((string) $role),
                password: $plainPassword,
                passwordChangeRequired: $mustChange,
            ));
        } catch (BusinessRuleViolation $violation) {
            $this->components->error($violation->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Akun dibuat (ID %d, NIP %s).', $userId, Nip::fromString((string) $nip)->masked()));

        return self::SUCCESS;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
