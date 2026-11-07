<?php

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\DateRange;
use App\Domain\Shared\InvalidDateRange;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    private static function range(string $start, string $end): DateRange
    {
        return new DateRange(new DateTimeImmutable($start), new DateTimeImmutable($end));
    }

    /**
     * @return iterable<string, array{string, string, string, string, bool}>
     */
    public static function pairs(): iterable
    {
        yield 'bersinggungan satu hari di akhir' => ['2026-11-01', '2026-11-09', '2026-11-09', '2026-11-20', true];
        yield 'bersinggungan satu hari di awal' => ['2026-11-09', '2026-11-20', '2026-11-01', '2026-11-09', true];
        yield 'di dalam' => ['2026-11-01', '2026-11-30', '2026-11-10', '2026-11-12', true];
        yield 'melingkupi' => ['2026-11-10', '2026-11-12', '2026-11-01', '2026-11-30', true];
        yield 'sama persis satu hari' => ['2026-11-05', '2026-11-05', '2026-11-05', '2026-11-05', true];
        yield 'di luar, hari berikutnya' => ['2026-11-01', '2026-11-09', '2026-11-10', '2026-11-20', false];
        yield 'di luar, jauh sebelum' => ['2026-01-01', '2026-01-31', '2026-11-01', '2026-11-09', false];
        yield 'lintas tahun beririsan' => ['2026-12-20', '2027-01-05', '2027-01-05', '2027-01-10', true];
        yield 'lintas tahun tidak beririsan' => ['2026-12-20', '2026-12-31', '2027-01-01', '2027-01-10', false];
    }

    #[DataProvider('pairs')]
    public function test_overlap_is_inclusive_and_symmetric(string $aStart, string $aEnd, string $bStart, string $bEnd, bool $expected): void
    {
        $a = self::range($aStart, $aEnd);
        $b = self::range($bStart, $bEnd);

        $this->assertSame($expected, $a->overlaps($b));
        $this->assertSame($expected, $b->overlaps($a));
    }

    public function test_time_of_day_is_ignored(): void
    {
        $morning = self::range('2026-11-09 23:59', '2026-11-09 23:59');
        $range = self::range('2026-11-01 08:00', '2026-11-09 00:00');

        $this->assertTrue($range->overlaps($morning));
        $this->assertTrue($range->contains(new DateTimeImmutable('2026-11-09 23:00')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2026-11-10 00:00')));
    }

    public function test_end_before_start_is_rejected(): void
    {
        $this->expectException(InvalidDateRange::class);
        self::range('2026-11-10', '2026-11-09');
    }

    public function test_labels(): void
    {
        $this->assertSame('1–9 Nov', self::range('2026-11-01', '2026-11-09')->label());
        $this->assertSame('5 Agu', self::range('2026-08-05', '2026-08-05')->label());
        $this->assertSame('28 Okt–3 Nov', self::range('2026-10-28', '2026-11-03')->label());
        $this->assertSame('28 Des 2026–3 Jan 2027', self::range('2026-12-28', '2027-01-03')->label());

        $this->assertSame('1–10 Desember 2026', self::range('2026-12-01', '2026-12-10')->longLabel());
        $this->assertSame('5 Agustus 2026', self::range('2026-08-05', '2026-08-05')->longLabel());
        $this->assertSame('28 Oktober–3 November 2026', self::range('2026-10-28', '2026-11-03')->longLabel());
        $this->assertSame('28 Desember 2026–3 Januari 2027', self::range('2026-12-28', '2027-01-03')->longLabel());
    }
}
