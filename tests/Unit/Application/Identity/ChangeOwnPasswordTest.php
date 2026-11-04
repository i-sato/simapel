<?php

namespace Tests\Unit\Application\Identity;

use App\Application\Identity\ChangeOwnPassword;
use App\Application\Identity\ChangeOwnPasswordInput;
use App\Application\Identity\Exceptions\CurrentPasswordIncorrect;
use App\Application\Identity\Exceptions\NewPasswordMustDiffer;
use App\Application\Identity\Exceptions\UserAccountNotFound;
use App\Application\Identity\NewUserAccount;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Nip;
use App\Domain\Identity\PasswordPolicy;
use App\Domain\Identity\Role;
use App\Domain\Identity\WeakPassword;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\FakePasswordHasher;
use Tests\Support\Fakes\FixedClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemoryUserAccountRepository;
use Tests\Support\Fakes\SpySessionTerminator;

final class ChangeOwnPasswordTest extends TestCase
{
    private const CURRENT = 'Sandi-Lama-2026!';

    private const NEW = 'Sandi-Baru-2026!';

    private InMemoryUserAccountRepository $users;

    private InMemoryAuditTrail $audit;

    private SpySessionTerminator $sessions;

    private ChangeOwnPassword $useCase;

    private int $userId;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserAccountRepository;
        $this->audit = new InMemoryAuditTrail;
        $this->sessions = new SpySessionTerminator;
        $this->useCase = new ChangeOwnPassword(
            $this->users,
            new FakePasswordHasher,
            new PasswordPolicy,
            $this->sessions,
            $this->audit,
            new ImmediateTransaction,
            new FixedClock(new DateTimeImmutable('2026-11-04 10:30:00')),
        );

        $this->userId = $this->users->create(new NewUserAccount(
            nip: Nip::fromString('999900000000000003'),
            name: 'Pegawai Uji',
            email: null,
            role: Role::Pegawai,
            passwordHash: 'hashed:'.self::CURRENT,
            passwordChangeRequired: true,
        ));
    }

    private function change(string $current = self::CURRENT, string $new = self::NEW, ?int $userId = null): void
    {
        $this->useCase->handle(new ChangeOwnPasswordInput(
            userId: $userId ?? $this->userId,
            currentPassword: $current,
            newPassword: $new,
            currentSessionId: 'sesi-aktif',
        ));
    }

    public function test_updates_hash_clears_required_flag_and_stamps_time(): void
    {
        $this->change();

        $record = $this->users->accounts[$this->userId];
        $this->assertSame('hashed:'.self::NEW, $record['passwordHash']);
        $this->assertFalse($record['passwordChangeRequired']);
        $this->assertSame('2026-11-04 10:30:00', $record['passwordChangedAt']?->format('Y-m-d H:i:s'));
    }

    public function test_audits_change_and_terminates_other_sessions_only(): void
    {
        $this->change();

        $this->assertSame(AuditEventType::PasswordChanged, $this->audit->events[0]->type);
        $this->assertSame($this->userId, $this->audit->events[0]->actorId);
        $this->assertSame([['userId' => $this->userId, 'keep' => 'sesi-aktif']], $this->sessions->otherSessionCalls);
        $this->assertSame([], $this->sessions->allSessionCalls);
    }

    public function test_rejects_wrong_current_password_without_changes(): void
    {
        try {
            $this->change(current: 'Salah-Sandi-2026!');
            $this->fail('CurrentPasswordIncorrect seharusnya dilempar.');
        } catch (CurrentPasswordIncorrect $exception) {
            $this->assertSame('current_password', $exception->field());
        }

        $this->assertSame('hashed:'.self::CURRENT, $this->users->accounts[$this->userId]['passwordHash']);
        $this->assertSame([], $this->audit->events);
        $this->assertSame([], $this->sessions->otherSessionCalls);
    }

    public function test_rejects_reusing_current_password(): void
    {
        $this->expectException(NewPasswordMustDiffer::class);
        $this->change(new: self::CURRENT);
    }

    public function test_rejects_weak_new_password(): void
    {
        $this->expectException(WeakPassword::class);
        $this->change(new: 'lemah');
    }

    public function test_rejects_unknown_account(): void
    {
        $this->expectException(UserAccountNotFound::class);
        $this->change(userId: 999);
    }
}
