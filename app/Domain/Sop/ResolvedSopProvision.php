<?php

declare(strict_types=1);

namespace App\Domain\Sop;

final readonly class ResolvedSopProvision
{
    public function __construct(
        public SopProvision $provision,
        public bool $isFallback,
    ) {}
}
