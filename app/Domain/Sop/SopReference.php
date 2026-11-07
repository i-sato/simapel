<?php

declare(strict_types=1);

namespace App\Domain\Sop;

use App\Domain\Shared\HttpUrl;
use DateTimeImmutable;

/**
 * Acuan dokumen SOP resmi (nomor, tanggal efektif, link).
 */
final readonly class SopReference
{
    private function __construct(
        public ?string $number,
        public ?DateTimeImmutable $effectiveDate,
        public ?HttpUrl $url,
    ) {}

    /**
     * @throws InvalidSopProvision
     */
    public static function of(?string $number, ?DateTimeImmutable $effectiveDate, ?HttpUrl $url): self
    {
        $number = $number === null ? null : trim($number);

        if ($number !== null && mb_strlen($number) > 100) {
            throw new InvalidSopProvision('Nomor SOP maksimal 100 karakter.', 'sop_number');
        }

        return new self($number === '' ? null : $number, $effectiveDate, $url);
    }

    public function isComplete(): bool
    {
        return $this->number !== null && $this->url !== null;
    }
}
