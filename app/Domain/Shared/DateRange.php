<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;

/**
 * Rentang tanggal inklusif (jam diabaikan). Dua rentang beririsan bila berbagi
 * minimal satu hari, termasuk bila hanya bersinggungan di tanggal batas.
 */
final readonly class DateRange
{
    private const MONTHS = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const LONG_MONTHS = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public DateTimeImmutable $start;

    public DateTimeImmutable $end;

    /**
     * @throws InvalidDateRange
     */
    public function __construct(DateTimeImmutable $start, DateTimeImmutable $end)
    {
        $start = $start->setTime(0, 0);
        $end = $end->setTime(0, 0);

        if ($end < $start) {
            throw new InvalidDateRange;
        }

        $this->start = $start;
        $this->end = $end;
    }

    public function overlaps(self $other): bool
    {
        return $this->start <= $other->end && $other->start <= $this->end;
    }

    public function contains(DateTimeImmutable $date): bool
    {
        $day = $date->setTime(0, 0);

        return $day >= $this->start && $day <= $this->end;
    }

    /**
     * Label ringkas berbahasa Indonesia, mis. "1–9 Nov", "28 Okt–3 Nov", "28 Des 2026–3 Jan 2027".
     */
    public function label(): string
    {
        $startDay = (int) $this->start->format('j');
        $endDay = (int) $this->end->format('j');
        $startMonth = self::MONTHS[(int) $this->start->format('n')];
        $endMonth = self::MONTHS[(int) $this->end->format('n')];

        if ($this->start->format('Y') !== $this->end->format('Y')) {
            return sprintf('%d %s %s–%d %s %s', $startDay, $startMonth, $this->start->format('Y'), $endDay, $endMonth, $this->end->format('Y'));
        }

        if ($startMonth !== $endMonth) {
            return sprintf('%d %s–%d %s', $startDay, $startMonth, $endDay, $endMonth);
        }

        return $startDay === $endDay
            ? sprintf('%d %s', $startDay, $startMonth)
            : sprintf('%d–%d %s', $startDay, $endDay, $startMonth);
    }

    /**
     * Label lengkap untuk pesan, mis. "1–10 Desember 2026", "28 November–3 Desember 2026".
     */
    public function longLabel(): string
    {
        $startDay = (int) $this->start->format('j');
        $endDay = (int) $this->end->format('j');
        $startMonth = self::LONG_MONTHS[(int) $this->start->format('n')];
        $endMonth = self::LONG_MONTHS[(int) $this->end->format('n')];
        $startYear = $this->start->format('Y');
        $endYear = $this->end->format('Y');

        if ($startYear !== $endYear) {
            return sprintf('%d %s %s–%d %s %s', $startDay, $startMonth, $startYear, $endDay, $endMonth, $endYear);
        }

        if ($startMonth !== $endMonth) {
            return sprintf('%d %s–%d %s %s', $startDay, $startMonth, $endDay, $endMonth, $endYear);
        }

        return $startDay === $endDay
            ? sprintf('%d %s %s', $startDay, $startMonth, $endYear)
            : sprintf('%d–%d %s %s', $startDay, $endDay, $startMonth, $endYear);
    }
}
