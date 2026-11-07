<?php

declare(strict_types=1);

namespace App\Application\Workload;

/**
 * Pegawai pada matriks beban kerja (tanpa NIP; K9 transparansi beban kerja).
 */
final readonly class WorkloadEmployee
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $jabatan,
    ) {}
}
