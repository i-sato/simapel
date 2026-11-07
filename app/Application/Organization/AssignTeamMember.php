<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Organization\Exceptions\EmployeeNotAssignable;
use App\Application\Organization\Exceptions\WorkTeamNotFound;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\TeamRole;

/**
 * Menambahkan pegawai ke tim kerja atau mengubah perannya (Ketua/Sekretaris/Anggota).
 */
final readonly class AssignTeamMember
{
    public function __construct(
        private WorkTeamRepository $teams,
        private UserAccountRepository $users,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(int $teamId, int $employeeId, TeamRole $role, int $actorId): void
    {
        $this->transaction->run(function () use ($teamId, $employeeId, $role, $actorId): void {
            $team = $this->teams->find($teamId) ?? throw new WorkTeamNotFound;
            $employee = $this->users->find($employeeId);

            if ($employee === null || ! $employee->isActive) {
                throw new EmployeeNotAssignable;
            }

            $composition = $this->teams->compositionForUpdate($team->id);
            $previousRole = $composition->roleOf($employee->id);

            if ($previousRole === $role) {
                return;
            }

            $composition->assign($employee->id, $role);
            $this->teams->saveMember($team->id, $employee->id, $role);

            $this->audit->record(new AuditEvent(
                type: $previousRole === null ? AuditEventType::TeamMemberAssigned : AuditEventType::TeamMemberRoleChanged,
                description: $previousRole === null
                    ? sprintf('%s ditambahkan ke %s sebagai %s.', $employee->nip->masked(), $team->label(), $role->label())
                    : sprintf('Peran %s di %s diubah dari %s menjadi %s.', $employee->nip->masked(), $team->label(), $previousRole->label(), $role->label()),
                actorId: $actorId,
                subjectType: 'work_team',
                subjectId: $team->id,
                context: [
                    'employee_id' => $employee->id,
                    'nip_masked' => $employee->nip->masked(),
                    'role' => $role->value,
                    'previous_role' => $previousRole?->value,
                ],
            ));
        });
    }
}
