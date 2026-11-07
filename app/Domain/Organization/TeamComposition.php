<?php

declare(strict_types=1);

namespace App\Domain\Organization;

/**
 * Susunan anggota satu tim kerja pada satu tahun.
 *
 * Aturan: satu pegawai memegang satu peran di tim ini; Ketua dan Sekretaris
 * masing-masing maksimal satu orang. Tim baru boleh belum memiliki Ketua
 * (ditandai lewat hasLeader()), karena susunan diisi bertahap.
 * Pegawai yang sama boleh menjadi anggota tim lain.
 */
final readonly class TeamComposition
{
    /**
     * @param  array<int, TeamRole>  $members  employeeId => peran
     */
    public function __construct(private array $members = []) {}

    public function roleOf(int $employeeId): ?TeamRole
    {
        return $this->members[$employeeId] ?? null;
    }

    public function holderOf(TeamRole $role): ?int
    {
        foreach ($this->members as $employeeId => $memberRole) {
            if ($memberRole === $role) {
                return $employeeId;
            }
        }

        return null;
    }

    public function hasLeader(): bool
    {
        return $this->holderOf(TeamRole::Ketua) !== null;
    }

    /**
     * Menambah anggota atau mengubah perannya.
     *
     * @throws SingleSeatRoleTaken
     */
    public function assign(int $employeeId, TeamRole $role): self
    {
        if ($role->isSingleSeat()) {
            $holder = $this->holderOf($role);

            if ($holder !== null && $holder !== $employeeId) {
                throw new SingleSeatRoleTaken($role);
            }
        }

        $members = $this->members;
        $members[$employeeId] = $role;

        return new self($members);
    }

    /**
     * @throws NotATeamMember
     */
    public function remove(int $employeeId): self
    {
        if (! isset($this->members[$employeeId])) {
            throw new NotATeamMember;
        }

        $members = $this->members;
        unset($members[$employeeId]);

        return new self($members);
    }
}
