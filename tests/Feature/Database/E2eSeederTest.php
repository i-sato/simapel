<?php

namespace Tests\Feature\Database;

use App\Domain\Identity\Role;
use App\Models\User;
use Database\Seeders\E2eSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

final class E2eSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['simapel.e2e.nip' => '999900000000000001', 'simapel.e2e.password' => 'Sandi-E2E-2026!']);
    }

    public function test_seeds_three_synthetic_accounts_idempotently(): void
    {
        $this->seed(E2eSeeder::class);
        $this->seed(E2eSeeder::class);

        $this->assertSame(3, User::query()->count());

        $active = User::query()->where('nip', '999900000000000001')->sole();
        $this->assertSame(Role::SuperAdminRegional, $active->role);
        $this->assertTrue(Hash::check('Sandi-E2E-2026!', $active->password));
        $this->assertFalse(User::query()->where('nip', '999900000000000002')->sole()->is_active);
        $this->assertTrue(User::query()->where('nip', '999900000000000003')->sole()->password_change_required);
    }

    public function test_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        try {
            $this->app->make(E2eSeeder::class)->run();
            $this->fail('E2eSeeder tidak boleh berjalan di production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('hanya boleh dijalankan', $exception->getMessage());
        }

        $this->assertSame(0, User::query()->count());
    }

    public function test_requires_configured_credentials(): void
    {
        config(['simapel.e2e.password' => null]);

        $this->expectException(RuntimeException::class);
        $this->seed(E2eSeeder::class);
    }

    public function test_default_seeder_creates_no_accounts(): void
    {
        $this->seed();

        $this->assertSame(0, User::query()->count());
    }
}
