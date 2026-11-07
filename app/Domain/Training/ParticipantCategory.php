<?php

declare(strict_types=1);

namespace App\Domain\Training;

enum ParticipantCategory: string
{
    case Umum = 'umum';
    case Disabilitas = 'disabilitas';

    public function label(): string
    {
        return match ($this) {
            self::Umum => 'Umum',
            self::Disabilitas => 'Disabilitas',
        };
    }
}
