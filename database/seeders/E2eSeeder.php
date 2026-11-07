<?php

namespace Database\Seeders;

use App\Domain\Identity\Nip;
use App\Domain\Identity\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Akun sintetis untuk uji UI (Playwright). Idempoten: aman dijalankan berulang
 * dan tidak menghapus data lain. Ditolak di luar lingkungan local/testing/e2e.
 *
 * NIP dasar (E2E_NIP) harus berakhiran 1; akun turunan memakai akhiran 2, 3, dan 4.
 */
final class E2eSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'e2e'])) {
            throw new RuntimeException('E2eSeeder hanya boleh dijalankan di lingkungan local, testing, atau e2e.');
        }

        $baseNip = (string) config('simapel.e2e.nip');
        $password = (string) config('simapel.e2e.password');

        if (! Nip::isValid($baseNip) || ! str_ends_with($baseNip, '1')) {
            throw new RuntimeException('E2E_NIP wajib 18 digit dan berakhiran 1.');
        }

        if (mb_strlen($password) < 12) {
            throw new RuntimeException('E2E_PASSWORD wajib diisi (minimal 12 karakter).');
        }

        $prefix = substr($baseNip, 0, 17);

        $accounts = [
            ['nip' => $baseNip, 'name' => 'Pengguna E2E', 'role' => Role::SuperAdmin, 'is_active' => true, 'password_change_required' => false],
            ['nip' => $prefix.'2', 'name' => 'Pengguna E2E Nonaktif', 'role' => Role::Pegawai, 'is_active' => false, 'password_change_required' => false],
            ['nip' => $prefix.'3', 'name' => 'Pengguna E2E Ganti Sandi', 'role' => Role::Pegawai, 'is_active' => true, 'password_change_required' => true],
            ['nip' => $prefix.'4', 'name' => 'Pengguna E2E Pegawai', 'role' => Role::Pegawai, 'is_active' => true, 'password_change_required' => false],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['nip' => $account['nip']],
                [...$account, 'email' => null, 'password' => $password],
            );
        }

        $this->command?->info('Akun E2E siap (NIP dasar '.Nip::fromString($baseNip)->masked().').');
    }
}
