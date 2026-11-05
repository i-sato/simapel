<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

use App\Application\Shared\Contracts\Clock;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        // CarbonImmutable menghormati Carbon::setTestNow() dan timezone aplikasi.
        return CarbonImmutable::now();
    }
}
