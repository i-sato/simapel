<?php

namespace Tests\Feature;

use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\TeamRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WorkTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkTeamTest extends TestCase
{
    use RefreshDatabase;

    private function team(string $code = 'KDD'): WorkTeam
    {
        return WorkTeam::query()->where('code', $code)->where('year', 2026)->sole();
    }

    public function test_three_work_teams_for_2026_are_seeded_by_migration(): void
    {
        $this->assertSame(['ASN', 'DTA', 'KDD'], WorkTeam::query()->where('year', 2026)->orderBy('code')->get()->map(fn (WorkTeam $team): string => $team->code->value)->all());
    }

    public function test_every_employee_can_view_teams_and_members_read_only(): void
    {
        $leader = User::factory()->create(['name' => 'Ketua KDD', 'nip' => '999900000000000501']);
        $this->team()->memberships()->create(['user_id' => $leader->id, 'role' => TeamRole::Ketua]);

        $this->actingAs(User::factory()->create())
            ->get('/work-teams')
            ->assertOk()
            ->assertSee('Keterampilan Digital Dasar')
            ->assertSee('Ketua KDD')
            ->assertSee('Ketua belum ditetapkan');

        $this->get('/work-teams/'.$this->team()->id)
            ->assertOk()
            ->assertSee('Ketua KDD')
            ->assertDontSee('999900000000000501')
            ->assertDontSee('Tambah anggota')
            ->assertDontSee('Keluarkan');
    }

    public function test_pegawai_cannot_change_membership(): void
    {
        $member = User::factory()->create();
        $team = $this->team();
        $this->actingAs(User::factory()->create());

        $this->post("/work-teams/{$team->id}/members", ['employee_id' => $member->id, 'role' => 'anggota'])->assertForbidden();
        $this->put("/work-teams/{$team->id}/members/{$member->id}", ['role' => 'ketua'])->assertForbidden();
        $this->delete("/work-teams/{$team->id}/members/{$member->id}")->assertForbidden();

        $this->assertDatabaseCount('team_memberships', 0);
    }

    public function test_super_admin_assigns_changes_and_removes_members(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->create();
        $team = $this->team('DTA');
        $this->actingAs($admin)->from("/work-teams/{$team->id}");

        $this->post("/work-teams/{$team->id}/members", ['employee_id' => $employee->id, 'role' => 'anggota'])->assertSessionHasNoErrors();
        $this->put("/work-teams/{$team->id}/members/{$employee->id}", ['role' => 'ketua'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('team_memberships', ['work_team_id' => $team->id, 'user_id' => $employee->id, 'role' => 'ketua']);

        $this->delete("/work-teams/{$team->id}/members/{$employee->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseCount('team_memberships', 0);

        $types = AuditLog::query()->where('subject_type', 'work_team')->pluck('event_type')->map(fn ($type) => $type->value)->all();
        $this->assertSame([
            AuditEventType::TeamMemberAssigned->value,
            AuditEventType::TeamMemberRoleChanged->value,
            AuditEventType::TeamMemberRemoved->value,
        ], $types);
    }

    public function test_second_leader_and_inactive_employee_are_rejected(): void
    {
        $team = $this->team('ASN');
        $first = User::factory()->create();
        $second = User::factory()->create();
        $inactive = User::factory()->inactive()->create();
        $this->actingAs(User::factory()->superAdmin()->create())->from("/work-teams/{$team->id}");

        $this->post("/work-teams/{$team->id}/members", ['employee_id' => $first->id, 'role' => 'ketua']);
        $this->post("/work-teams/{$team->id}/members", ['employee_id' => $second->id, 'role' => 'ketua'])
            ->assertSessionHasErrors(['role']);
        $this->post("/work-teams/{$team->id}/members", ['employee_id' => $inactive->id, 'role' => 'anggota'])
            ->assertSessionHasErrors(['employee_id' => 'Pegawai tidak ditemukan atau akunnya tidak aktif.']);

        $this->assertSame(1, $team->memberships()->count());
    }

    public function test_employee_may_belong_to_several_teams(): void
    {
        $employee = User::factory()->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        foreach (['KDD', 'ASN', 'DTA'] as $code) {
            $this->post('/work-teams/'.$this->team($code)->id.'/members', ['employee_id' => $employee->id, 'role' => 'sekretaris'])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, $employee->teamMemberships()->count());
    }
}
