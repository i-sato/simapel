<?php

namespace Tests\Unit\Application\Identity;

use App\Application\Identity\Exceptions\CannotChangeOwnAccess;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\Exceptions\LastSuperAdminRequired;
use App\Application\Identity\Exceptions\NipAlreadyRegistered;
use App\Application\Identity\Exceptions\UserAccountNotFound;
use App\Application\Identity\ResetEmployeePassword;
use App\Application\Identity\SetEmployeeActive;
use App\Application\Identity\UpdateEmployee;
use App\Application\Identity\UpdateEmployeeInput;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\PasswordPolicy;
use App\Domain\Identity\Role;
use App\Domain\Identity\WeakPassword;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\FakePasswordHasher;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemoryUserAccountRepository;
use Tests\Support\Fakes\SpySessionTerminator;

final class EmployeeAdministrationTest extends TestCase
{
    private InMemoryUserAccountRepository $users;

    private InMemoryAuditTrail $audit;

    private SpySessionTerminator $sessions;

    private int $admin;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserAccountRepository;
        $this->audit = new InMemoryAuditTrail;
        $this->sessions = new SpySessionTerminator;
        $this->admin = $this->users->seed('999900000000000001', Role::SuperAdmin, name: 'Admin Uji');
    }

    private function update(): UpdateEmployee
    {
        return new UpdateEmployee($this->users, $this->audit, new ImmediateTransaction);
    }

    private function setActive(): SetEmployeeActive
    {
        return new SetEmployeeActive($this->users, $this->sessions, $this->audit, new ImmediateTransaction);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function input(int $userId, array $overrides = []): UpdateEmployeeInput
    {
        $values = array_merge([
            'userId' => $userId,
            'nip' => '999900000000000050',
            'name' => 'Pegawai Diubah',
            'email' => 'diubah@example.test',
            'pangkatGolongan' => 'Penata - III/c',
            'jabatan' => 'Analis',
            'role' => Role::Pegawai,
            'canBeAdmin' => true,
        ], $overrides);

        return new UpdateEmployeeInput(...$values);
    }

    public function test_updates_profile_role_and_admin_capability(): void
    {
        $employee = $this->users->seed('999900000000000010');

        $this->update()->handle($this->input($employee), $this->admin);

        $record = $this->users->accounts[$employee];
        $this->assertSame('999900000000000050', $record['nip']->value);
        $this->assertSame('Analis', $record['profile']->jabatan);
        $this->assertTrue($record['canBeAdmin']);
        $this->assertSame(AuditEventType::EmployeeUpdated, $this->audit->events[0]->type);
        $this->assertTrue($this->audit->events[0]->context['nip_changed']);
    }

    public function test_keeping_own_nip_and_email_is_not_a_duplicate(): void
    {
        $employee = $this->users->seed('999900000000000011', email: 'tetap@example.test');

        $this->update()->handle($this->input($employee, ['nip' => '999900000000000011', 'email' => 'tetap@example.test']), $this->admin);

        $this->assertFalse($this->audit->events[0]->context['nip_changed']);
    }

    public function test_rejects_nip_or_email_of_another_account(): void
    {
        $employee = $this->users->seed('999900000000000012');
        $this->users->seed('999900000000000013', email: 'dipakai@example.test');

        try {
            $this->update()->handle($this->input($employee, ['nip' => '999900000000000013']), $this->admin);
            $this->fail('NIP milik akun lain seharusnya ditolak.');
        } catch (NipAlreadyRegistered) {
        }

        $this->expectException(EmailAlreadyRegistered::class);
        $this->update()->handle($this->input($employee, ['email' => 'dipakai@example.test']), $this->admin);
    }

    public function test_super_admin_cannot_demote_self(): void
    {
        $this->users->seed('999900000000000014', Role::SuperAdmin);

        $this->expectException(CannotChangeOwnAccess::class);
        $this->update()->handle($this->input($this->admin, ['nip' => '999900000000000001']), $this->admin);
    }

    public function test_last_active_super_admin_cannot_be_demoted(): void
    {
        $other = $this->users->seed('999900000000000015', Role::SuperAdmin);
        $this->users->setActive($this->admin, false);

        $this->expectException(LastSuperAdminRequired::class);
        $this->update()->handle($this->input($other, ['nip' => '999900000000000015']), 999);
    }

    public function test_super_admin_may_be_demoted_when_another_remains(): void
    {
        $other = $this->users->seed('999900000000000016', Role::SuperAdmin);

        $this->update()->handle($this->input($other, ['nip' => '999900000000000016']), $this->admin);

        $this->assertSame(Role::Pegawai, $this->users->accounts[$other]['role']);
    }

    public function test_deactivation_ends_all_sessions_and_is_audited(): void
    {
        $employee = $this->users->seed('999900000000000017');

        $this->setActive()->handle($employee, false, $this->admin);

        $this->assertFalse($this->users->accounts[$employee]['isActive']);
        $this->assertSame([$employee], $this->sessions->allSessionCalls);
        $this->assertSame(AuditEventType::EmployeeDeactivated, $this->audit->events[0]->type);
    }

    public function test_activation_does_not_touch_sessions(): void
    {
        $employee = $this->users->seed('999900000000000018', active: false);

        $this->setActive()->handle($employee, true, $this->admin);

        $this->assertTrue($this->users->accounts[$employee]['isActive']);
        $this->assertSame([], $this->sessions->allSessionCalls);
        $this->assertSame(AuditEventType::EmployeeActivated, $this->audit->events[0]->type);
    }

    public function test_cannot_deactivate_self_or_last_super_admin(): void
    {
        try {
            $this->setActive()->handle($this->admin, false, $this->admin);
            $this->fail('Menonaktifkan akun sendiri seharusnya ditolak.');
        } catch (CannotChangeOwnAccess) {
        }

        $this->expectException(LastSuperAdminRequired::class);
        $this->setActive()->handle($this->admin, false, 999);
    }

    public function test_unknown_account_is_rejected(): void
    {
        $this->expectException(UserAccountNotFound::class);
        $this->setActive()->handle(999, false, $this->admin);
    }

    public function test_password_reset_sets_temporary_password_and_ends_sessions(): void
    {
        $employee = $this->users->seed('999900000000000019');
        $reset = new ResetEmployeePassword($this->users, new FakePasswordHasher, new PasswordPolicy, $this->sessions, $this->audit, new ImmediateTransaction);

        $reset->handle($employee, 'Sementara-2026!', $this->admin);

        $this->assertSame('hashed:Sementara-2026!', $this->users->accounts[$employee]['passwordHash']);
        $this->assertTrue($this->users->accounts[$employee]['passwordChangeRequired']);
        $this->assertSame([$employee], $this->sessions->allSessionCalls);
        $this->assertSame(AuditEventType::EmployeePasswordReset, $this->audit->events[0]->type);

        $this->expectException(WeakPassword::class);
        $reset->handle($employee, 'lemah', $this->admin);
    }
}
