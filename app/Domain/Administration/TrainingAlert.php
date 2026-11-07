<?php

declare(strict_types=1);

namespace App\Domain\Administration;

final readonly class TrainingAlert
{
    public function __construct(
        public AlertKind $kind,
        public string $label,
        public string $detail,
        public ?DocumentType $document = null,
    ) {}

    public function severity(): AlertSeverity
    {
        return $this->kind->severity();
    }
}
