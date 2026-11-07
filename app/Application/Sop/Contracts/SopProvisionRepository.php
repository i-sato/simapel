<?php

declare(strict_types=1);

namespace App\Application\Sop\Contracts;

use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\SopProvision;

interface SopProvisionRepository
{
    public function find(int $id): ?SopProvision;

    /**
     * Semua versi satu tim kerja (seluruh tahun). Dengan $lock, implementasi wajib
     * mengunci baris terkait agar aktivasi bersamaan tidak menghasilkan dua versi Berlaku.
     *
     * @return list<SopProvision>
     */
    public function forTeam(WorkTeamCode $team, bool $lock = false): array;

    public function nextVersion(WorkTeamCode $team, int $fiscalYear): int;

    /**
     * @return int ID baru
     */
    public function insert(SopProvision $provision, int $actorId): int;

    public function save(SopProvision $provision, int $actorId): void;

    public function delete(int $id): void;

    /**
     * Apakah ketentuan sudah disalin/dirujuk pelatihan (langkah 3 MVP-1).
     */
    public function isReferenced(int $id): bool;
}
