<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * Peran global akun.
 *
 * Peran Ketua Pelatihan dan Anggota Tim bersifat kontekstual per pelatihan
 * (TOR 2.1) sehingga tidak termasuk di sini.
 */
enum Role: string
{
    case SuperAdminRegional = 'super_admin_regional';
    case AdminRegional = 'admin_regional';
    case Pegawai = 'pegawai';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdminRegional => 'Super Admin Regional',
            self::AdminRegional => 'Admin Regional',
            self::Pegawai => 'Pegawai',
        };
    }

    public function isAdministrator(): bool
    {
        return $this === self::SuperAdminRegional || $this === self::AdminRegional;
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
