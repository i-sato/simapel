<?php

namespace Tests\Support\Fakes;

use App\Application\Sop\Contracts\SopProvisionRepository;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\SopProvision;

final class InMemorySopProvisionRepository implements SopProvisionRepository
{
    /** @var array<int, SopProvision> */
    public array $provisions = [];

    /** @var list<int> */
    public array $referenced = [];

    public int $locks = 0;

    private int $nextId = 1;

    public function find(int $id): ?SopProvision
    {
        return $this->provisions[$id] ?? null;
    }

    public function forTeam(WorkTeamCode $team, bool $lock = false): array
    {
        if ($lock) {
            $this->locks++;
        }

        return array_values(array_filter($this->provisions, fn (SopProvision $provision): bool => $provision->team === $team));
    }

    public function nextVersion(WorkTeamCode $team, int $fiscalYear): int
    {
        $versions = array_map(
            fn (SopProvision $provision): int => $provision->version,
            array_filter($this->provisions, fn (SopProvision $provision): bool => $provision->team === $team && $provision->fiscalYear === $fiscalYear),
        );

        return ($versions === [] ? 0 : max($versions)) + 1;
    }

    public function insert(SopProvision $provision, int $actorId): int
    {
        $id = $this->nextId++;
        $this->provisions[$id] = $provision->withId($id);

        return $id;
    }

    public function save(SopProvision $provision, int $actorId): void
    {
        $this->provisions[(int) $provision->id] = $provision;
    }

    public function delete(int $id): void
    {
        unset($this->provisions[$id]);
    }

    public function isReferenced(int $id): bool
    {
        return in_array($id, $this->referenced, true);
    }
}
