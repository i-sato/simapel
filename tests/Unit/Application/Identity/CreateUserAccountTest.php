<?php

namespace Tests\Unit\Application\Identity;

use App\Application\Identity\CreateUserAccount;
use App\Application\Identity\CreateUserAccountInput;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\Exceptions\NipAlreadyRegistered;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\InvalidEmployeeProfile;
use App\Domain\Identity\InvalidNip;
use App\Domain\Identity\PasswordPolicy;
use App\Domain\Identity\Role;
use App\Domain\Identity\WeakPassword;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\FakePasswordHasher;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemoryUserAccountRepository;

final class CreateUserAccountTest extends TestCase
{
    private InMemoryUserAccountRepository $users;

    private InMemoryAuditTrail $audit;

    private ImmediateTransaction $transaction;

    private CreateUserAccount $useCase;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserAccountRepository;
        $this->audit = new InMemoryAuditTrail;
        $this->transaction = new ImmediateTransaction;
        $this->useCase = new CreateUserAccount(
            $this->users,
            new FakePasswordHasher,
            new PasswordPolicy,
            $this->audit,
            $this->transaction,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function input(array $overrides = []): CreateUserAccountInput
    {
        $values = array_merge([
            'nip' => '999900000000000001',
            'name' => 'Admin Regional Uji',
            'email' => 'admin.uji@example.test',
            'role' => Role::Pegawai,
            'pangkatGolongan' => '  Penata Muda -  III/a ',
            'jabatan' => 'Pranata Komputer',
            'canBeAdmin' => true,
            'password' => 'Pelatihan-2026!',
            'passwordChangeRequired' => true,
        ], $overrides);

        return new CreateUserAccountInput(...$values);
    }

    public function test_creates_account_with_hashed_password_inside_a_transaction(): void
    {
        $id = $this->useCase->handle($this->input(), actorId: 7);

        $record = $this->users->accounts[$id];
        $this->assertSame('999900000000000001', $record['nip']->value);
        $this->assertSame(Role::Pegawai, $record['role']);
        $this->assertTrue($record['canBeAdmin']);
        $this->assertSame('Penata Muda - III/a', $record['profile']->pangkatGolongan);
        $this->assertSame('Pranata Komputer', $record['profile']->jabatan);
        $this->assertSame('hashed:Pelatihan-2026!', $record['passwordHash']);
        $this->assertTrue($record['passwordChangeRequired']);
        $this->assertSame(1, $this->transaction->runs);
    }

    public function test_records_audit_event_with_masked_nip_only(): void
    {
        $id = $this->useCase->handle($this->input(), actorId: 7);

        $this->assertCount(1, $this->audit->events);
        $event = $this->audit->events[0];
        $this->assertSame(AuditEventType::UserCreated, $event->type);
        $this->assertSame(7, $event->actorId);
        $this->assertSame($id, $event->subjectId);
        $this->assertSame('9999**********0001', $event->context['nip_masked']);
        $this->assertStringNotContainsString('999900000000000001', $event->description.json_encode($event->context));
    }

    public function test_normalizes_email_and_treats_blank_email_as_absent(): void
    {
        $first = $this->useCase->handle($this->input(['email' => '  Admin.Uji@Example.TEST ']));
        $second = $this->useCase->handle($this->input(['nip' => '999900000000000002', 'email' => '   ']));

        $this->assertSame('admin.uji@example.test', $this->users->accounts[$first]['profile']->email);
        $this->assertNull($this->users->accounts[$second]['profile']->email);
    }

    public function test_rejects_duplicate_nip_without_side_effects(): void
    {
        $this->useCase->handle($this->input());

        try {
            $this->useCase->handle($this->input(['email' => 'lain@example.test']));
            $this->fail('NipAlreadyRegistered seharusnya dilempar.');
        } catch (NipAlreadyRegistered $exception) {
            $this->assertSame('nip', $exception->field());
        }

        $this->assertCount(1, $this->users->accounts);
        $this->assertCount(1, $this->audit->events);
    }

    public function test_rejects_duplicate_email(): void
    {
        $this->useCase->handle($this->input());

        $this->expectException(EmailAlreadyRegistered::class);
        $this->useCase->handle($this->input(['nip' => '999900000000000002']));
    }

    public function test_rejects_invalid_nip(): void
    {
        $this->expectException(InvalidNip::class);
        $this->useCase->handle($this->input(['nip' => '12345']));
    }

    public function test_rejects_weak_password_before_hashing(): void
    {
        $this->expectException(WeakPassword::class);
        $this->useCase->handle($this->input(['password' => 'pendek']));
    }

    public function test_rejects_blank_name(): void
    {
        $this->expectException(InvalidEmployeeProfile::class);
        $this->useCase->handle($this->input(['name' => '   ']));
    }

    public function test_rejects_malformed_email(): void
    {
        try {
            $this->useCase->handle($this->input(['email' => 'bukan-email']));
            $this->fail('InvalidEmployeeProfile seharusnya dilempar.');
        } catch (InvalidEmployeeProfile $exception) {
            $this->assertSame('email', $exception->field());
        }

        $this->assertSame([], $this->users->accounts);
    }
}
