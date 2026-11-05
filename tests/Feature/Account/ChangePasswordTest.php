<?php

namespace Tests\Feature\Account;

use App\Domain\Audit\AuditEventType;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD = 'Sandi-Baru-2026!';

    public function test_user_with_initial_password_is_forced_to_password_page(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/account/password')
            ->assertSessionHas('warning');

        $this->get('/account/password')
            ->assertOk()
            ->assertSee('Ganti kata sandi awal');
    }

    public function test_forced_user_can_still_log_out(): void
    {
        $this->actingAs(User::factory()->mustChangePassword()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_password_change_updates_credentials_and_unlocks_application(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $this->actingAs($user)
            ->from('/account/password')
            ->put('/account/password', [
                'current_password' => UserFactory::PASSWORD,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertNotNull($fresh);
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $fresh->password));
        $this->assertFalse($fresh->password_change_required);
        $this->assertNotNull($fresh->password_changed_at);
        $this->assertDatabaseHas('audit_logs', ['event_type' => AuditEventType::PasswordChanged->value, 'actor_user_id' => $user->id]);

        // Request berikutnya memuat ulang akun dari database seperti pada sesi nyata.
        $this->actingAs($fresh)->get('/dashboard')->assertOk();
    }

    public function test_wrong_current_password_is_reported_on_its_field_without_flashing_secrets(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/account/password')
            ->put('/account/password', [
                'current_password' => 'Bukan-Sandi-2026!',
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertRedirect('/account/password')
            ->assertSessionHasErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.'])
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.current_password');

        $this->assertTrue(Hash::check(UserFactory::PASSWORD, (string) $user->fresh()?->password));
    }

    public function test_weak_password_is_rejected_with_indonesian_messages(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/account/password')
            ->put('/account/password', [
                'current_password' => UserFactory::PASSWORD,
                'password' => 'lemahsekali',
                'password_confirmation' => 'lemahsekali',
            ])
            ->assertSessionHasErrors('password');

        $message = (string) session('errors')?->first('password');
        $this->assertStringContainsString('kata sandi baru', $message);
    }

    public function test_confirmation_must_match(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/account/password')
            ->put('/account/password', [
                'current_password' => UserFactory::PASSWORD,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => 'Berbeda-Sandi-2026!',
            ])
            ->assertSessionHasErrors(['password' => 'Konfirmasi kata sandi baru tidak sama.']);
    }
}
