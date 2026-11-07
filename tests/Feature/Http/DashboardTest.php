<?php

namespace Tests\Feature\Http;

use App\Domain\Identity\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_account_summary_with_masked_nip_only(): void
    {
        $user = User::factory()->role(Role::Pegawai)->create(['nip' => '999900000000004321', 'name' => 'Ratna Uji']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Ratna Uji')
            ->assertSee('9999**********4321')
            ->assertDontSee('999900000000004321')
            ->assertSee('Pegawai');
    }

    public function test_layout_renders_navigation_with_active_item(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertSee('MENU UTAMA')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Keamanan Akun')
            ->assertSee('Lewati ke konten utama');
    }

    public function test_static_assets_are_versioned(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertSee('css/simapel.css?v=', false)
            ->assertSee('js/simapel.js?v=', false);
    }
}
