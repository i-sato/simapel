<?php

declare(strict_types=1);

namespace App\Domain\Training;

use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Sop\ResolvedSopProvision;

/**
 * Salinan ketentuan SOP tim kerja yang disimpan pelatihan saat finalisasi (ADR 0010).
 * Perubahan ketentuan sesudahnya tidak menggeser pelatihan ini.
 */
final readonly class SopSnapshot
{
    public function __construct(
        public int $provisionId,
        public int $fiscalYear,
        public int $version,
        public ?string $sopNumber,
        public ?string $sopUrl,
        public AdministrativeDeadlines $deadlines,
        public bool $isFallback,
    ) {}

    public static function fromResolved(ResolvedSopProvision $resolved): self
    {
        $provision = $resolved->provision;

        if ($provision->id === null) {
            throw new InvalidTraining('Ketentuan SOP belum tersimpan.', 'general');
        }

        return new self(
            $provision->id,
            $provision->fiscalYear,
            $provision->version,
            $provision->sop->number,
            $provision->sop->url?->value,
            $provision->deadlines,
            $resolved->isFallback,
        );
    }
}
