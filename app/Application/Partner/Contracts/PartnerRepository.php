<?php

declare(strict_types=1);

namespace App\Application\Partner\Contracts;

use App\Domain\Partner\Partner;

interface PartnerRepository
{
    public function find(int $id): ?Partner;

    /**
     * Mitra dengan nama yang sama setelah dinormalisasi (Partner::normalize).
     */
    public function findByNormalizedName(string $normalized): ?Partner;

    public function insert(Partner $partner, int $actorId): int;

    public function update(Partner $partner, int $actorId): void;
}
