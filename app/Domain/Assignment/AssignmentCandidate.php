<?php

declare(strict_types=1);

namespace App\Domain\Assignment;

/**
 * Fakta pegawai yang menentukan kelayakan penugasan.
 */
final readonly class AssignmentCandidate
{
    public function __construct(
        public int $employeeId,
        public string $name,
        public bool $isActive,
        public bool $canBeAdmin,
    ) {}

    /**
     * Admin hanya dari pegawai aktif bertanda "dapat menjadi Admin"; Panitia boleh
     * siapa saja yang aktif, termasuk Ketua dan Sekretaris tim lain.
     *
     * @throws AssignmentRejected
     */
    public function assertEligibleFor(AssignmentRole $role): void
    {
        if (! $this->isActive) {
            throw new AssignmentRejected(sprintf('%s tidak aktif sehingga tidak dapat ditugaskan.', $this->name), $role === AssignmentRole::Admin ? 'admins' : 'panitia');
        }

        if ($role === AssignmentRole::Admin && ! $this->canBeAdmin) {
            throw new AssignmentRejected(sprintf('%s belum bertanda "dapat menjadi Admin". Super Admin dapat menandainya di halaman Pegawai.', $this->name), 'admins');
        }
    }
}
