<?php

declare(strict_types=1);

namespace App\Domain\Workload;

use App\Domain\Shared\DateRange;
use DateTimeImmutable;

/**
 * Membagi rentang tanggal menjadi periode utuh: minggu Senin–Minggu atau bulan kalender.
 * Periode pertama dan terakhir dapat melampaui rentang agar setiap kolom selalu satu
 * minggu/bulan penuh.
 */
final class PeriodGrouping
{
    public const MAX_PERIODS = 60;

    private const MONTHS = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /**
     * @return list<Period>
     */
    public static function periods(DateRange $range, Granularity $granularity): array
    {
        $periods = [];
        $cursor = $granularity === Granularity::Minggu
            ? $range->start->modify('monday this week')
            : $range->start->modify('first day of this month');

        while ($cursor <= $range->end && count($periods) < self::MAX_PERIODS) {
            $end = $granularity === Granularity::Minggu
                ? $cursor->modify('+6 days')
                : $cursor->modify('last day of this month');
            $period = new DateRange($cursor, $end);
            $periods[] = new Period($period, $granularity === Granularity::Minggu ? $period->label() : self::monthLabel($cursor));
            $cursor = $end->modify('+1 day');
        }

        return $periods;
    }

    /**
     * Rentang bawaan: awal bulan berjalan sampai akhir bulan kedua berikutnya (3 bulan).
     */
    public static function defaultRange(DateTimeImmutable $today): DateRange
    {
        $start = $today->setTime(0, 0)->modify('first day of this month');

        return new DateRange($start, $start->modify('+2 months')->modify('last day of this month'));
    }

    private static function monthLabel(DateTimeImmutable $date): string
    {
        return self::MONTHS[(int) $date->format('n')].' '.$date->format('Y');
    }
}
