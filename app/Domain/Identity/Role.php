<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * Peran global akun (ADR 0008).
 *
 * Peran di tim kerja (Ketua/Sekretaris/Anggota) ada di App\Domain\Organization,
 * kapabilitas Admin berupa tanda pada pegawai, dan Admin/Panitia adalah
 * penugasan per pelatihan. Semuanya bukan peran global.
 */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Pegawai = 'pegawai';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Pegawai => 'Pegawai',
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * @return array<string, string> value => label, untuk pilihan formulir/CLI
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
