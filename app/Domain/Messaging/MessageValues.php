<?php

declare(strict_types=1);

namespace App\Domain\Messaging;

/**
 * Nilai placeholder untuk satu pelatihan. Nilai kosong ditampilkan "(menyusul)".
 */
final readonly class MessageValues
{
    public const PENDING = '(menyusul)';

    /**
     * @param  array<string, string|null>  $values  nilai Placeholder => teks
     */
    public function __construct(private array $values) {}

    public function for(Placeholder $placeholder): string
    {
        $value = $this->values[$placeholder->value] ?? null;

        return $value === null || trim($value) === '' ? self::PENDING : $value;
    }
}
