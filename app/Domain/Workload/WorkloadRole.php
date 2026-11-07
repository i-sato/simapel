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

    /** Ketua tidak pernah dinilai bentrok karena membawahi seluruh pelatihan timnya (K19). */
    public function countsForConflict(): bool
    {
        return $this !== self::Ketua;
    }

    /**
     * Dua peran pada pelatihan berbeda yang beririsan dinilai bentrok bila keduanya bukan
     * Ketua dan tidak keduanya Sekretaris (K19): administrasi tim sendiri relatif ringan,
     * tetapi Sekretaris yang juga bertugas sebagai Admin/Panitia di pelatihan lain dinilai bentrok.
     */
    public function conflictsWith(self $other): bool
    {
        return $this->countsForConflict()
            && $other->countsForConflict()
            && ! ($this === self::Sekretaris && $other === self::Sekretaris);
    }
}
