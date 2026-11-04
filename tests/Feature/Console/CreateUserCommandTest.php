<?php

namespace Tests\Feature\Console;

use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

final class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Sandi-Awal-2026!';

    /**
     * @param  array<string, string|bool>  $parameters
     */
    private function command(array $parameters): PendingCommand
    {
        $command = $this->artisan('simapel:user:create', $parameters);
        $this->assertInstanceOf(PendingCommand::class, $command);

        return $command;
    }

    public function test_creates_super_admin_with_forced_password_change_by_default(): void
    {
        $this->command([
            '--nip' => '999900000000000010',
            '--name' => 'Super Admin Pertama',
            '--email' => 'Super.Admin@Example.TEST',
            '--role' => Role::SuperAdminRegional->value,
        ])
            ->expectsQuestion('Kata sandi awal', self::PASSWORD)
            ->expectsQuestion('Ulangi kata sandi', self::PASSWORD)
            ->expectsConfirmation('Wajibkan ganti kata sandi saat login pertama?', 'yes')
            ->assertSuccessful();

        $user = User::query()->where('nip', '999900000000000010')->sole();
        $this->assertSame(Role::SuperAdminRegional, $user->role);
        $this->assertSame('super.admin@example.test', $user->email);
        $this->assertTrue($user->password_change_required);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditEventType::UserCreated->value,
            'subject_id' => $user->id,
            'actor_user_id' => null,
            'channel' => 'cli',
        ]);
    }

    public function test_permanent_password_option_skips_forced_change(): void
    {
        $this->command([
            '--nip' => '999900000000000011',
            '--name' => 'Pegawai Tetap',
            '--email' => '',
            '--role' => Role::Pegawai->value,
            '--permanent-password' => true,
        ])
            ->expectsQuestion('Email (opsional)', '')
            ->expectsQuestion('Kata sandi awal', self::PASSWORD)
            ->expectsQuestion('Ulangi kata sandi', self::PASSWORD)
            ->assertSuccessful();

        $user = User::query()->where('nip', '999900000000000011')->sole();
        $this->assertFalse($user->password_change_required);
        $this->assertNull($user->email);
    }

    public function test_rejects_duplicate_nip(): void
    {
        User::factory()->create(['nip' => '999900000000000012']);

        $this->command([
            '--nip' => '999900000000000012',
            '--name' => 'Duplikat',
            '--email' => 'duplikat@example.test',
            '--role' => Role::Pegawai->value,
            '--permanent-password' => true,
        ])
            ->expectsQuestion('Kata sandi awal', self::PASSWORD)
            ->expectsQuestion('Ulangi kata sandi', self::PASSWORD)
            ->expectsOutputToContain('NIP sudah terdaftar')
            ->assertFailed();

        $this->assertSame(1, User::query()->where('nip', '999900000000000012')->count());
    }

    public function test_rejects_mismatched_confirmation(): void
    {
        $this->command([
            '--nip' => '999900000000000013',
            '--name' => 'Salah Konfirmasi',
            '--email' => 'salah@example.test',
            '--role' => Role::Pegawai->value,
        ])
            ->expectsQuestion('Kata sandi awal', self::PASSWORD)
            ->expectsQuestion('Ulangi kata sandi', 'Sandi-Lain-2026!')
            ->expectsOutputToContain('Konfirmasi kata sandi tidak sama')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_rejects_unknown_role(): void
    {
        $this->command([
            '--nip' => '999900000000000014',
            '--name' => 'Peran Asing',
            '--email' => 'asing@example.test',
            '--role' => 'ketua_pelatihan',
        ])
            ->expectsOutputToContain('Peran tidak dikenal')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
