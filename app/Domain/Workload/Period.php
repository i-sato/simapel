<?php

declare(strict_types=1);

namespace App\Domain\Workload;

use App\Domain\Shared\DateRange;

final readonly class Period
{
    public function __construct(public DateRange $range, public string $label) {}
}
