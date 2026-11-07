<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Organization\Exceptions\WorkTeamNotFound;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;

final readonly class RemoveTeamMember
{
    public function __construct(
        private WorkTeamRepository $teams,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(int $teamId, int $employeeId, int $actorId): void
    {
        $this->transaction->run(function () use ($teamId, $employeeId, $actorId): void {
            $team = $this->teams->find($teamId) ?? throw new WorkTeamNotFound;
            $composition = $this->teams->compositionForUpdate($team->id);
            $role = $composition->roleOf($employeeId);

            $composition->remove($employeeId);
            $this->teams->removeMember($team->id, $employeeId);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::TeamMemberRemoved,
                description: sprintf('Anggota dengan peran %s dikeluarkan dari %s.', $role?->label() ?? '-', $team->label()),
                actorId: $actorId,
                subjectType: 'work_team',
                subjectId: $team->id,
                context: ['employee_id' => $employeeId, 'role' => $role?->value],
            ));
        });
    }
}
