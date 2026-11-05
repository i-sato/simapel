<?php

namespace Tests\Feature\Auth;

use App\Domain\Audit\AuditEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SessionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_ends_session_and_is_audited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionHas('success', 'Anda telah keluar dari SIMAPEL.');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditEventType::LoggedOut->value,
            'actor_user_id' => $user->id,
        ]);
    }

    public function test_deactivated_account_loses_its_existing_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->get('/dashboard')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('nip');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditEventType::SessionRevoked->value,
            'subject_id' => $user->id,
        ]);
    }

    public function test_logout_requires_post(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/logout')
            ->assertStatus(405);
    }
}
