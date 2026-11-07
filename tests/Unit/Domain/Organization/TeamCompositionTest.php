<?php

namespace Tests\Unit\Domain\Organization;

use App\Domain\Organization\NotATeamMember;
use App\Domain\Organization\SingleSeatRoleTaken;
use App\Domain\Organization\TeamComposition;
use App\Domain\Organization\TeamRole;
use App\Domain\Organization\WorkTeamCode;
use PHPUnit\Framework\TestCase;

final class TeamCompositionTest extends TestCase
{
    public function test_new_team_has_no_leader_until_one_is_assigned(): void
    {
        $team = new TeamComposition;
        $this->assertFalse($team->hasLeader());

        $team = $team->assign(1, TeamRole::Ketua);
        $this->assertTrue($team->hasLeader());
        $this->assertSame(1, $team->holderOf(TeamRole::Ketua));
    }

    public function test_many_members_are_allowed(): void
    {
        $team = (new TeamComposition)
            ->assign(1, TeamRole::Anggota)
            ->assign(2, TeamRole::Anggota)
            ->assign(3, TeamRole::Anggota);

        $this->assertSame(TeamRole::Anggota, $team->roleOf(3));
    }

    public function test_second_leader_is_rejected(): void
    {
        $team = (new TeamComposition)->assign(1, TeamRole::Ketua);

        try {
            $team->assign(2, TeamRole::Ketua);
            $this->fail('Ketua kedua seharusnya ditolak.');
        } catch (SingleSeatRoleTaken $exception) {
            $this->assertSame('role', $exception->field());
            $this->assertStringContainsString('Ketua', $exception->getMessage());
        }
    }

    public function test_second_secretary_is_rejected(): void
    {
        $this->expectException(SingleSeatRoleTaken::class);

        (new TeamComposition)->assign(1, TeamRole::Sekretaris)->assign(2, TeamRole::Sekretaris);
    }

    public function test_reassigning_the_same_holder_is_allowed(): void
    {
        $team = (new TeamComposition)->assign(1, TeamRole::Ketua)->assign(1, TeamRole::Ketua);

        $this->assertSame(1, $team->holderOf(TeamRole::Ketua));
    }

    public function test_leader_can_be_replaced_after_previous_leader_steps_down(): void
    {
        $team = (new TeamComposition)
            ->assign(1, TeamRole::Ketua)
            ->assign(2, TeamRole::Anggota)
            ->assign(1, TeamRole::Anggota)
            ->assign(2, TeamRole::Ketua);

        $this->assertSame(2, $team->holderOf(TeamRole::Ketua));
        $this->assertSame(TeamRole::Anggota, $team->roleOf(1));
    }

    public function test_one_person_holds_one_role_per_team(): void
    {
        $team = (new TeamComposition)->assign(1, TeamRole::Sekretaris)->assign(1, TeamRole::Ketua);

        $this->assertSame(TeamRole::Ketua, $team->roleOf(1));
        $this->assertNull($team->holderOf(TeamRole::Sekretaris));
    }

    public function test_composition_is_immutable(): void
    {
        $original = new TeamComposition;
        $original->assign(1, TeamRole::Ketua);

        $this->assertFalse($original->hasLeader());
    }

    public function test_remove_member_and_reject_non_member(): void
    {
        $team = (new TeamComposition)->assign(1, TeamRole::Ketua)->remove(1);
        $this->assertNull($team->roleOf(1));

        $this->expectException(NotATeamMember::class);
        $team->remove(1);
    }

    public function test_team_codes_and_role_labels(): void
    {
        $this->assertSame(['KDD', 'ASN', 'DTA'], array_map(fn (WorkTeamCode $code): string => $code->value, WorkTeamCode::cases()));
        $this->assertSame('Talenta Digital', WorkTeamCode::Dta->defaultName());
        $this->assertSame(['ketua' => 'Ketua', 'sekretaris' => 'Sekretaris', 'anggota' => 'Anggota'], TeamRole::options());
        $this->assertTrue(TeamRole::Sekretaris->isSingleSeat());
        $this->assertFalse(TeamRole::Anggota->isSingleSeat());
    }
}
