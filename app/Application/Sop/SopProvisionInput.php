<?php

declare(strict_types=1);

namespace App\Application\Sop;

final readonly class SopProvisionInput
{
    public function __construct(
        public ?string $sopNumber,
        public ?string $sopEffectiveDate,
        public ?string $sopUrl,
        public string $effectiveFrom,
        public int $bapDays,
        public int $skDays,
        public int $stDays,
        public int $readyDays,
        public ?string $note = null,
    ) {}
}
