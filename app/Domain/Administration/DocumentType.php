<?php

declare(strict_types=1);

namespace App\Domain\Administration;

use App\Domain\Sop\AdministrativeDeadlines;

/**
 * Dokumen administrasi pelatihan (K3): BAP penetapan peserta, SK peserta, Surat Tugas.
 */
enum DocumentType: string
{
    case Bap = 'bap';
    case Sk = 'sk';
    case St = 'st';

    public function label(): string
    {
        return match ($this) {
            self::Bap => 'BAP',
            self::Sk => 'SK',
            self::St => 'ST',
        };
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::Bap => 'BAP penetapan peserta',
            self::Sk => 'SK peserta',
            self::St => 'Surat Tugas',
        };
    }

    public function deadlineDays(AdministrativeDeadlines $deadlines): int
    {
        return match ($this) {
            self::Bap => $deadlines->bapDays,
            self::Sk => $deadlines->skDays,
            self::St => $deadlines->stDays,
        };
    }
}
