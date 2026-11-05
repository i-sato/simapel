<?php

namespace Tests\Support\Fakes;

use App\Application\Shared\Contracts\Clock;
use DateTimeImmutable;

final readonly class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $now = new DateTimeImmutable('2026-11-04 09:00:00')) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
