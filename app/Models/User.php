<?php

namespace App\Models;

use App\Domain\Identity\Nip;
use App\Domain\Identity\Role;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Model persistensi akun (layer Infrastructure). Aturan bisnis akun berada di
 * App\Domain\Identity dan App\Application\Identity.
 *
 * @property int $id
 * @property string $nip
 * @property string $name
 * @property string|null $email
 * @property Role $role
 * @property bool $is_active
 * @property bool $password_change_required
 * @property CarbonImmutable|null $password_changed_at
 * @property CarbonImmutable|null $last_login_at
 */
#[Fillable([
    'nip', 'name', 'email', 'role', 'is_active', 'password',
    'password_change_required', 'password_changed_at', 'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * Nilai awal sama dengan default kolom, agar instance baru (sebelum dimuat ulang
     * dari database) tetap lolos Model::shouldBeStrict().
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'password_change_required' => false,
        'password_changed_at' => null,
        'last_login_at' => null,
        'remember_token' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'is_active' => 'boolean',
            'password' => 'hashed',
            'password_change_required' => 'boolean',
            'password_changed_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
        ];
    }

    public function nipValue(): Nip
    {
        return Nip::fromString($this->nip);
    }

    public function maskedNip(): string
    {
        return $this->nipValue()->masked();
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
