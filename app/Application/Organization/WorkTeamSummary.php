<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Domain\Organization\WorkTeamCode;

final readonly class WorkTeamSummary
{
    public function __construct(
        public int $id,
        public WorkTeamCode $code,
        public string $name,
        public int $year,
        public ?string $registrationGuideUrl = null,
    ) {}

    public function label(): string
    {
        return sprintf('%s (%s) %d', $this->name, $this->code->value, $this->year);
    }
}
