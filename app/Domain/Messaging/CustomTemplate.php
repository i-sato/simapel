<?php

declare(strict_types=1);

namespace App\Domain\Messaging;

/**
 * Template kustom milik satu anggota untuk satu pelatihan.
 */
final readonly class CustomTemplate
{
    public function __construct(
        public ?int $id,
        public int $trainingId,
        public int $ownerId,
        public TemplateBody $body,
    ) {}
}
