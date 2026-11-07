<?php

namespace Tests\Unit\Application\Identity;

use App\Application\Identity\RecordSuccessfulLogin;
use App\Domain\Audit\AuditEventType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\FixedClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemoryUserAccountRepository;

final class RecordSuccessfulLoginTest extends TestCase
{
    public function test_stamps_last_login_and_records_audit_event_atomically(): void
    {
        $users = new InMemoryUserAccountRepository;
        $audit = new InMemoryAuditTrail;
        $transaction = new ImmediateTransaction;
        $userId = $users->seed('999900000000000001');

        (new RecordSuccessfulLogin($users, $audit, $transaction, new FixedClock(new DateTimeImmutable('2026-11-04 07:45:00'))))
            ->handle($userId);

        $this->assertSame('2026-11-04 07:45:00', $users->accounts[$userId]['lastLoginAt']?->format('Y-m-d H:i:s'));
        $this->assertSame(AuditEventType::LoginSucceeded, $audit->events[0]->type);
        $this->assertSame($userId, $audit->events[0]->subjectId);
        $this->assertSame(1, $transaction->runs);
    }
}
