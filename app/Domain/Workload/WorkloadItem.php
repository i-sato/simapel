<?php

declare(strict_types=1);

namespace App\Domain\Workload;

use App\Domain\Shared\DateRange;

/**
 * Satu peran seorang pegawai pada satu pelatihan, dengan rentang beban kerja
 * awal pendaftaran sampai akhir pelatihan (K8).
 */
final readonly class WorkloadItem
{
    public function __construct(
        public int $employeeId,
        public int $trainingId,
        public string $trainingName,
        public string $teamCode,
        public WorkloadRole $role,
        public DateRange $range,
    ) {}
}
