<?php

declare(strict_types=1);

namespace App\Domain\Workload;

/**
 * Peran yang membentuk beban kerja: penugasan per pelatihan (Admin/Panitia) dan
 * peran tim kerja atas pelatihan timnya (Ketua/Sekretaris).
 */
enum WorkloadRole: string
{
    case Admin = 'admin';
    case Panitia = 'panitia';
    case Ketua = 'ketua';
    case Sekretaris = 'sekretaris';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Panitia => 'Panitia',
            self::Ketua => 'Ketua tim',
            self::Sekretaris => 'Sekretaris tim',
        };
    }

    /** Hanya penugasan per pelatihan yang dinilai bentrok (aturan langkah 4). */
    public function countsForConflict(): bool
    {
        return $this === self::Admin || $this === self::Panitia;
    }
}
