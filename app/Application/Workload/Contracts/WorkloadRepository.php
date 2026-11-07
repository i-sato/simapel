<?php

declare(strict_types=1);

namespace App\Application\Workload\Contracts;

use App\Application\Workload\WorkloadEmployee;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Shared\DateRange;
use App\Domain\Workload\WorkloadItem;

interface WorkloadRepository
{
    /**
     * Peran pegawai pada pelatihan yang rentangnya (awal pendaftaran–akhir pelatihan)
     * beririsan dengan rentang: Admin/Panitia dari penugasan, Ketua/Sekretaris dari tim
     * kerja pelatihan. Implementasi wajib memakai jumlah query tetap (tanpa N+1).
     *
     * @param  WorkTeamCode|null  $team  hanya pelatihan tim kerja ini
     * @param  int|null  $employeeId  hanya pegawai ini
     * @return list<WorkloadItem>
     */
    public function items(DateRange $range, ?WorkTeamCode $team = null, ?int $employeeId = null): array;

    /**
     * Pegawai aktif untuk baris matriks. Tanpa filter: semua pegawai aktif. Dengan filter
     * tim: anggota tim kerja tersebut pada tahun-tahun dalam rentang, ditambah $includeIds
     * (mis. Panitia lintas tim pada pelatihan tim tersebut).
     *
     * @param  list<int>  $years
     * @param  list<int>  $includeIds
     * @return list<WorkloadEmployee>
     */
    public function employees(?WorkTeamCode $team, array $years, array $includeIds = []): array;

    public function employee(int $employeeId): ?WorkloadEmployee;
}
