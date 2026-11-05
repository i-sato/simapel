<?php

namespace Database\Factories;

use App\Domain\Identity\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Data sintetis untuk pengujian. NIP diawali 9999 agar tidak menyerupai NIP nyata.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public const PASSWORD = 'Rahasia-Uji-123!';

    protected static ?string $passwordHash = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nip' => '9999'.fake()->unique()->numerify('##############'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => Role::Pegawai,
            'is_active' => true,
            'password' => static::$passwordHash ??= Hash::make(self::PASSWORD),
            'password_change_required' => false,
        ];
    }

    public function role(Role $role): static
    {
        return $this->state(fn (): array => ['role' => $role]);
    }

    public function superAdmin(): static
    {
        return $this->role(Role::SuperAdminRegional);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn (): array => ['password_change_required' => true]);
    }
}
