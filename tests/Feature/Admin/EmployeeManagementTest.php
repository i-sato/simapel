<?php

namespace Tests\Feature\Admin;

use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Role;
use App\Models\User;
use App\Models\WorkTeam;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Sandi-Awal-2026!';

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_pegawai_cannot_manage_employees(): void
    {
        $pegawai = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($pegawai);

        $this->get('/employees')->assertForbidden()->assertSee('Akses ditolak');
        $this->get('/employees/create')->assertForbidden();
        $this->get('/employees/import')->assertForbidden();
        $this->get("/employees/{$other->id}/edit")->assertForbidden();
        $this->post('/employees', [])->assertForbidden();
        $this->put("/employees/{$other->id}", [])->assertForbidden();
        $this->post("/employees/{$other->id}/deactivate")->assertForbidden();
        $this->post("/employees/{$other->id}/password", [])->assertForbidden();

        $this->assertTrue($other->refresh()->is_active);
    }

    public function test_navigation_shows_employee_menu_only_to_super_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertDontSee('ADMINISTRASI')->assertSee('Tim Kerja');
        $this->actingAs($this->admin())->get('/dashboard')->assertSee('ADMINISTRASI')->assertSee('Pegawai');
    }

    public function test_list_masks_nip_and_supports_search(): void
    {
        User::factory()->create(['name' => 'Ratna Uji', 'nip' => '999900000000001234', 'jabatan' => 'Analis']);
        User::factory()->create(['name' => 'Budi Uji']);

        $this->actingAs($this->admin())
            ->get('/employees?q=Analis')
            ->assertOk()
            ->assertSee('Ratna Uji')
            ->assertSee('9999**********1234')
            ->assertDontSee('999900000000001234')
            ->assertDontSee('Budi Uji');
    }

    public function test_super_admin_creates_employee_with_profile_and_forced_password_change(): void
    {
        $this->actingAs($this->admin())
            ->post('/employees', [
                'nip' => '999900000000000301',
                'name' => 'Pegawai Baru',
                'email' => 'Baru@Example.TEST',
                'pangkat_golongan' => 'Penata Muda - III/a',
                'jabatan' => 'Pranata Komputer',
                'role' => 'pegawai',
                'can_be_admin' => '1',
                'password' => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
            ])
            ->assertRedirect('/employees')
            ->assertSessionHasNoErrors();

        $user = User::query()->where('nip', '999900000000000301')->sole();
        $this->assertSame('baru@example.test', $user->email);
        $this->assertSame('Penata Muda - III/a', $user->pangkat_golongan);
        $this->assertTrue($user->can_be_admin);
        $this->assertTrue($user->password_change_required);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
    }

    public function test_duplicate_nip_is_reported_on_its_field(): void
    {
        User::factory()->create(['nip' => '999900000000000302']);

        $this->actingAs($this->admin())
            ->from('/employees/create')
            ->post('/employees', [
                'nip' => '999900000000000302',
                'name' => 'Duplikat',
                'role' => 'pegawai',
                'password' => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
            ])
            ->assertRedirect('/employees/create')
            ->assertSessionHasErrors(['nip' => 'NIP sudah terdaftar pada akun lain.']);
    }

    public function test_super_admin_updates_employee(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}", [
                'nip' => $employee->nip,
                'name' => 'Nama Baru',
                'email' => '',
                'pangkat_golongan' => 'Penata - III/c',
                'jabatan' => 'Analis Kebijakan',
                'role' => 'super_admin',
                'can_be_admin' => '0',
            ])
            ->assertRedirect("/employees/{$employee->id}/edit")
            ->assertSessionHasNoErrors();

        $fresh = $employee->refresh();
        $this->assertSame('Nama Baru', $fresh->name);
        $this->assertNull($fresh->email);
        $this->assertSame(Role::SuperAdmin, $fresh->role);
        $this->assertDatabaseHas('audit_logs', ['event_type' => AuditEventType::EmployeeUpdated->value, 'subject_id' => $employee->id]);
    }

    public function test_super_admin_cannot_demote_or_deactivate_self(): void
    {
        $admin = $this->admin();
        User::factory()->superAdmin()->create();
        $this->actingAs($admin)->from("/employees/{$admin->id}/edit");

        $this->put("/employees/{$admin->id}", [
            'nip' => $admin->nip, 'name' => $admin->name, 'role' => 'pegawai',
        ])->assertSessionHasErrors(['role' => 'Anda tidak dapat menonaktifkan atau menurunkan peran akun Anda sendiri.']);

        $this->post("/employees/{$admin->id}/deactivate")->assertSessionHasErrors('role');

        $this->assertSame(Role::SuperAdmin, $admin->refresh()->role);
        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_deactivation_revokes_existing_sessions_and_activation_restores_access(): void
    {
        $employee = User::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->from("/employees/{$employee->id}/edit")
            ->post("/employees/{$employee->id}/deactivate")
            ->assertRedirect("/employees/{$employee->id}/edit");

        $this->assertFalse($employee->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['event_type' => AuditEventType::EmployeeDeactivated->value, 'subject_id' => $employee->id]);

        $this->post("/employees/{$employee->id}/activate");
        $this->assertTrue($employee->refresh()->is_active);
    }

    public function test_password_reset_requires_change_at_next_login(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($this->admin())->from("/employees/{$employee->id}/edit")
            ->post("/employees/{$employee->id}/password", [
                'password' => 'Sementara-2026!',
                'password_confirmation' => 'Sementara-2026!',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $employee->refresh();
        $this->assertTrue($fresh->password_change_required);
        $this->assertTrue(Hash::check('Sementara-2026!', (string) $fresh->password));
        $this->assertFalse(Hash::check(UserFactory::PASSWORD, (string) $fresh->password));
    }

    public function test_edit_page_shows_team_memberships(): void
    {
        $employee = User::factory()->create(['name' => 'Anggota Tim']);
        $team = WorkTeam::query()->where('code', 'DTA')->where('year', 2026)->sole();
        $team->memberships()->create(['user_id' => $employee->id, 'role' => 'sekretaris']);

        $this->actingAs($this->admin())
            ->get("/employees/{$employee->id}/edit")
            ->assertOk()
            ->assertSee('Talenta Digital (DTA)')
            ->assertSee('Sekretaris');
    }
}
