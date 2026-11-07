<?php

namespace Tests\Unit\Application\Organization;

use App\Application\Organization\AssignTeamMember;
use App\Application\Organization\Exceptions\EmployeeNotAssignable;
use App\Application\Organization\Exceptions\WorkTeamNotFound;
use App\Application\Organization\RemoveTeamMember;
use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\NotATeamMember;
use App\Domain\Organization\SingleSeatRoleTaken;
use App\Domain\Organization\TeamRole;
use App\Domain\Organization\WorkTeamCode;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemoryUserAccountRepository;
use Tests\Support\Fakes\InMemoryWorkTeamRepository;

final class TeamMembershipUseCasesTest extends TestCase
{
    private InMemoryWorkTeamRepository $teams;

    private InMemoryUserAccountRepository $users;

    private InMemoryAuditTrail $audit;

    private AssignTeamMember $assign;

    private RemoveTeamMember $remove;

    private int $teamId;

    protected function setUp(): void
    {
        $this->teams = new InMemoryWorkTeamRepository;
        $this->users = new InMemoryUserAccountRepository;
        $this->audit = new InMemoryAuditTrail;
        $transaction = new ImmediateTransaction;
        $this->assign = new AssignTeamMember($this->teams, $this->users, $this->audit, $transaction);
        $this->remove = new RemoveTeamMember($this->teams, $this->audit, $transaction);
        $this->teamId = $this->teams->addTeam(WorkTeamCode::Kdd);
    }

    public function test_assigns_member_with_lock_and_audit(): void
    {
        $employee = $this->users->seed('999900000000000101');

        $this->assign->handle($this->teamId, $employee, TeamRole::Ketua, actorId: 1);

        $this->assertSame(TeamRole::Ketua, $this->teams->members[$this->teamId][$employee]);
        $this->assertSame(1, $this->teams->locks);
        $this->assertSame(AuditEventType::TeamMemberAssigned, $this->audit->events[0]->type);
        $this->assertSame('ketua', $this->audit->events[0]->context['role']);
        $this->assertStringNotContainsString('999900000000000101', $this->audit->events[0]->description);
    }

    public function test_changing_role_is_audited_as_role_change(): void
    {
        $employee = $this->users->seed('999900000000000102');
        $this->assign->handle($this->teamId, $employee, TeamRole::Anggota, 1);
        $this->assign->handle($this->teamId, $employee, TeamRole::Sekretaris, 1);

        $this->assertSame(AuditEventType::TeamMemberRoleChanged, $this->audit->events[1]->type);
        $this->assertSame('anggota', $this->audit->events[1]->context['previous_role']);
    }

    public function test_assigning_the_same_role_again_is_a_no_op(): void
    {
        $employee = $this->users->seed('999900000000000103');
        $this->assign->handle($this->teamId, $employee, TeamRole::Anggota, 1);
        $this->assign->handle($this->teamId, $employee, TeamRole::Anggota, 1);

        $this->assertCount(1, $this->audit->events);
    }

    public function test_second_leader_is_rejected_without_saving(): void
    {
        $first = $this->users->seed('999900000000000104');
        $second = $this->users->seed('999900000000000105');
        $this->assign->handle($this->teamId, $first, TeamRole::Ketua, 1);

        try {
            $this->assign->handle($this->teamId, $second, TeamRole::Ketua, 1);
            $this->fail('Ketua kedua seharusnya ditolak.');
        } catch (SingleSeatRoleTaken) {
        }

        $this->assertArrayNotHasKey($second, $this->teams->members[$this->teamId]);
    }

    public function test_employee_may_join_several_teams(): void
    {
        $otherTeam = $this->teams->addTeam(WorkTeamCode::Dta);
        $employee = $this->users->seed('999900000000000106');

        $this->assign->handle($this->teamId, $employee, TeamRole::Ketua, 1);
        $this->assign->handle($otherTeam, $employee, TeamRole::Anggota, 1);

        $this->assertSame(TeamRole::Anggota, $this->teams->members[$otherTeam][$employee]);
    }

    public function test_inactive_or_unknown_employee_cannot_be_assigned(): void
    {
        $inactive = $this->users->seed('999900000000000107', active: false);

        try {
            $this->assign->handle($this->teamId, $inactive, TeamRole::Anggota, 1);
            $this->fail('Pegawai nonaktif seharusnya ditolak.');
        } catch (EmployeeNotAssignable) {
        }

        $this->expectException(EmployeeNotAssignable::class);
        $this->assign->handle($this->teamId, 999, TeamRole::Anggota, 1);
    }

    public function test_unknown_team_is_rejected(): void
    {
        $this->expectException(WorkTeamNotFound::class);
        $this->assign->handle(999, $this->users->seed('999900000000000108'), TeamRole::Anggota, 1);
    }

    public function test_remove_member_is_audited_and_non_member_is_rejected(): void
    {
        $employee = $this->users->seed('999900000000000109');
        $this->assign->handle($this->teamId, $employee, TeamRole::Sekretaris, 1);

        $this->remove->handle($this->teamId, $employee, 1);

        $this->assertArrayNotHasKey($employee, $this->teams->members[$this->teamId]);
        $this->assertSame(AuditEventType::TeamMemberRemoved, $this->audit->events[1]->type);

        $this->expectException(NotATeamMember::class);
        $this->remove->handle($this->teamId, $employee, 1);
    }
}
