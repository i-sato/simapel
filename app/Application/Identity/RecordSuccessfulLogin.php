<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Shared\Contracts\Clock;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;

final readonly class RecordSuccessfulLogin
{
    public function __construct(
        private UserAccountRepository $users,
        private AuditTrail $audit,
        private Transaction $transaction,
        private Clock $clock,
    ) {}

    public function handle(int $userId): void
    {
        $this->transaction->run(function () use ($userId): void {
            $this->users->recordLogin($userId, $this->clock->now());

            $this->audit->record(new AuditEvent(
                type: AuditEventType::LoginSucceeded,
                description: 'Login berhasil.',
                actorId: $userId,
                subjectType: 'user',
                subjectId: $userId,
            ));
        });
    }
}
