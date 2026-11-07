<?php

declare(strict_types=1);

namespace App\Application\Administration;

final readonly class DocumentInput
{
    public function __construct(
        public string $status,
        public ?string $number = null,
        public ?string $date = null,
        public ?string $fileUrl = null,
    ) {}
}
