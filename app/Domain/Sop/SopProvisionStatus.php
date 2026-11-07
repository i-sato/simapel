<?php

declare(strict_types=1);

namespace App\Domain\Sop;

enum SopProvisionStatus: string
{
    case Draf = 'draf';
    case Berlaku = 'berlaku';
    case Diganti = 'diganti';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Berlaku => 'Berlaku',
            self::Diganti => 'Diganti',
        };
    }

    /**
     * Versi yang pernah berlaku dan masih menentukan tanggal sebelum digantikan.
     */
    public function wasInForce(): bool
    {
        return $this !== self::Draf;
    }
}
