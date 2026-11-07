<?php

declare(strict_types=1);

namespace App\Domain\Training;

/**
 * Status waktu pelatihan, diturunkan dari tanggal (tidak disimpan).
 */
enum TrainingPhase: string
{
    case AkanDibuka = 'akan_dibuka';
    case Pendaftaran = 'pendaftaran';
    case PenetapanPeserta = 'penetapan_peserta';
    case Pelaksanaan = 'pelaksanaan';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::AkanDibuka => 'Akan dibuka',
            self::Pendaftaran => 'Pendaftaran',
            self::PenetapanPeserta => 'Penetapan peserta',
            self::Pelaksanaan => 'Pelaksanaan',
            self::Selesai => 'Selesai',
        };
    }
}
