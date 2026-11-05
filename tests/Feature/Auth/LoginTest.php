<?php

namespace Tests\Feature\Auth;

use App\Domain\Audit\AuditEventType;
use App\Models\AuditLog;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_rendered_for_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk ke SIMAPEL')
            ->assertSee('name="nip"', false);
    }

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/account/password')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_user_logs_in_with_nip_and_lands_on_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['nip' => $user->nip, 'password' => UserFactory::PASSWORD])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()?->last_login_at);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditEventType::LoginSucceeded->value,
            'actor_user_id' => $user->id,
            'channel' => 'web',
        ]);
    }

    public function test_wrong_password_is_rejected_with_generic_message_and_masked_audit(): void
    {
        $user = User::factory()->create();

        $this->from('/login')
            ->post('/login', ['nip' => $user->nip, 'password' => 'Salah-Sandi-2026!'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['nip' => 'NIP atau kata sandi salah, atau akun tidak aktif.']);

        $this->assertGuest();

        $log = AuditLog::query()->where('event_type', AuditEventType::LoginFailed)->sole();
        $this->assertSame($user->maskedNip(), $log->context['nip_masked'] ?? null);
        $this->assertStringNotContainsString($user->nip, (string) json_encode($log->toArray()));
    }

    public function test_unknown_nip_gets_the_same_message_as_wrong_password(): void
    {
        $this->post('/login', ['nip' => '999911111111111111', 'password' => 'Apa-Saja-2026!'])
            ->assertSessionHasErrors(['nip' => 'NIP atau kata sandi salah, atau akun tidak aktif.']);
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['nip' => $user->nip, 'password' => UserFactory::PASSWORD])
            ->assertSessionHasErrors(['nip' => 'NIP atau kata sandi salah, atau akun tidak aktif.']);

        $this->assertGuest();
    }

    public function test_email_is_not_a_login_identity(): void
    {
        User::factory()->create(['email' => 'pegawai@example.test']);

        $this->post('/login', ['nip' => 'pegawai@example.test', 'password' => UserFactory::PASSWORD])
            ->assertSessionHasErrors(['nip' => 'NIP harus terdiri dari tepat 18 digit angka.']);

        $this->assertGuest();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_login_is_throttled_per_nip_and_ip_after_max_attempts(): void
    {
        config(['simapel.login.max_attempts' => 3]);
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/login', ['nip' => $user->nip, 'password' => 'Salah-Sandi-2026!']);
        }

        $this->post('/login', ['nip' => $user->nip, 'password' => UserFactory::PASSWORD])
            ->assertSessionHasErrors('nip');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['event_type' => AuditEventType::LoginThrottled->value]);
        $this->assertStringContainsString('Terlalu banyak percobaan', (string) session('errors')?->first('nip'));
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_csrf_token_is_required_outside_test_environment(): void
    {
        $user = User::factory()->create();
        $this->app['env'] = 'local';

        $this->post('/login', ['nip' => $user->nip, 'password' => UserFactory::PASSWORD])
            ->assertStatus(419)
            ->assertSee('Sesi kedaluwarsa');

        $this->assertGuest();
    }
}
