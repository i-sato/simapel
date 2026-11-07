<?php

declare(strict_types=1);

namespace App\Domain\Assignment;

/**
 * Penugasan pegawai pada pelatihan. Alasan force diisi bila penugasan dibuat
 * walaupun beririsan dengan penugasan lain (K8).
 */
final readonly class TrainingAssignment
{
    public const MAX_REASON = 500;

    public ?string $forceReason;

    /**
     * @throws AssignmentRejected
     */
    public function __construct(
        public int $trainingId,
        public int $employeeId,
        public AssignmentRole $role,
        ?string $forceReason = null,
    ) {
        $forceReason = $forceReason === null ? null : trim($forceReason);

        if ($forceReason !== null && mb_strlen($forceReason) > self::MAX_REASON) {
            throw new AssignmentRejected(sprintf('Alasan maksimal %d karakter.', self::MAX_REASON), 'force_reason');
        }

        $this->forceReason = $forceReason === '' ? null : $forceReason;
    }

    public function key(): string
    {
        return $this->employeeId.':'.$this->role->value;
    }
}
