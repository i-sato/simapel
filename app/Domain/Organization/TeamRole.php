<?php

declare(strict_types=1);

namespace App\Domain\Organization;

enum TeamRole: string
{
    case Ketua = 'ketua';
    case Sekretaris = 'sekretaris';
    case Anggota = 'anggota';

    public function label(): string
    {
        return match ($this) {
            self::Ketua => 'Ketua',
            self::Sekretaris => 'Sekretaris',
            self::Anggota => 'Anggota',
        };
    }

    /**
     * Peran yang hanya boleh dipegang satu orang per tim per tahun.
     */
    public function isSingleSeat(): bool
    {
        return $this !== self::Anggota;
    }

    /**
     * @return array<string, string>
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
