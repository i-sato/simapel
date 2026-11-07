<?php

namespace Tests\Support\Fakes;

use App\Application\Partner\Contracts\PartnerRepository;
use App\Domain\Partner\Partner;

final class InMemoryPartnerRepository implements PartnerRepository
{
    /** @var array<int, Partner> */
    public array $partners = [];

    private int $nextId = 1;

    public function find(int $id): ?Partner
    {
        return $this->partners[$id] ?? null;
    }

    public function findByNormalizedName(string $normalized): ?Partner
    {
        foreach ($this->partners as $partner) {
            if ($partner->normalizedName() === $normalized) {
                return $partner;
            }
        }

        return null;
    }

    public function insert(Partner $partner, int $actorId): int
    {
        $id = $this->nextId++;
        $this->partners[$id] = new Partner($id, $partner->name, $partner->region);

        return $id;
    }

    public function update(Partner $partner, int $actorId): void
    {
        $this->partners[(int) $partner->id] = $partner;
    }
}
