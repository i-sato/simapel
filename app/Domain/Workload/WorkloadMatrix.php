<?php

declare(strict_types=1);

namespace App\Domain\Workload;

use App\Domain\Shared\DateRange;

/**
 * Matriks beban kerja pegawai × periode.
 *
 * - Jumlah per sel = banyaknya pelatihan berbeda yang rentangnya beririsan dengan periode
 *   (satu pegawai yang menjadi Admin sekaligus Panitia pada pelatihan yang sama dihitung satu).
 * - Bentrok = dua peran pada pelatihan berbeda saling beririsan dan irisannya jatuh di dalam
 *   periode, menurut WorkloadRole::conflictsWith() (K19): Ketua tidak pernah bentrok;
 *   Sekretaris bentrok dengan Admin/Panitia di pelatihan lain, tetapi tidak dengan tugas
 *   Sekretaris lain di timnya sendiri.
 */
final class WorkloadMatrix
{
    /**
     * @param  list<WorkloadItem>  $items
     * @param  list<Period>  $periods
     * @return array<int, list<WorkloadCell>> employeeId => sel per periode
     */
    public static function build(array $items, array $periods): array
    {
        $byEmployee = [];

        foreach ($items as $item) {
            $byEmployee[$item->employeeId][] = $item;
        }

        $matrix = [];

        foreach ($byEmployee as $employeeId => $employeeItems) {
            $overlaps = self::conflictOverlaps($employeeItems);
            $cells = [];

            foreach ($periods as $period) {
                $trainingIds = [];

                foreach ($employeeItems as $item) {
                    if ($item->range->overlaps($period->range)) {
                        $trainingIds[$item->trainingId] = $item->trainingId;
                    }
                }

                $conflict = false;

                foreach ($overlaps as $overlap) {
                    if ($overlap->overlaps($period->range)) {
                        $conflict = true;

                        break;
                    }
                }

                $cells[] = new WorkloadCell(array_values($trainingIds), $conflict);
            }

            $matrix[$employeeId] = $cells;
        }

        return $matrix;
    }

    /**
     * Pelatihan seorang pegawai yang bentrok dengan pelatihan lain.
     *
     * @param  list<WorkloadItem>  $items  milik satu pegawai
     * @return list<int>
     */
    public static function conflictingTrainings(array $items): array
    {
        $ids = [];
        $candidates = array_values(array_filter($items, fn (WorkloadItem $item): bool => $item->role->countsForConflict()));

        foreach ($candidates as $i => $a) {
            foreach (array_slice($candidates, $i + 1) as $b) {
                if ($a->trainingId !== $b->trainingId && $a->role->conflictsWith($b->role) && $a->range->overlaps($b->range)) {
                    $ids[$a->trainingId] = $a->trainingId;
                    $ids[$b->trainingId] = $b->trainingId;
                }
            }
        }

        sort($ids);

        return $ids;
    }

    /**
     * Rentang irisan antarperan yang bentrok.
     *
     * @param  list<WorkloadItem>  $items
     * @return list<DateRange>
     */
    private static function conflictOverlaps(array $items): array
    {
        $overlaps = [];
        $candidates = array_values(array_filter($items, fn (WorkloadItem $item): bool => $item->role->countsForConflict()));

        foreach ($candidates as $i => $a) {
            foreach (array_slice($candidates, $i + 1) as $b) {
                if ($a->trainingId !== $b->trainingId && $a->role->conflictsWith($b->role) && $a->range->overlaps($b->range)) {
                    $overlaps[] = new DateRange(max($a->range->start, $b->range->start), min($a->range->end, $b->range->end));
                }
            }
        }

        return $overlaps;
    }
}
