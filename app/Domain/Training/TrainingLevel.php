<?php

declare(strict_types=1);

namespace App\Domain\Training;

/**
 * Level pelatihan dalam learning path (K6): Level 1/2/3 = Dasar/Menengah/Mahir.
 */
enum TrainingLevel: string
{
    case Dasar = 'dasar';
    case Menengah = 'menengah';
    case Mahir = 'mahir';

    public function number(): int
    {
        return match ($this) {
            self::Dasar => 1,
            self::Menengah => 2,
            self::Mahir => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Dasar => 'Dasar',
            self::Menengah => 'Menengah',
            self::Mahir => 'Mahir',
        };
    }

    public function fullLabel(): string
    {
        return sprintf('Level %d · %s', $this->number(), $this->label());
    }
}
