<?php

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\InvalidTimeOfDay;
use App\Domain\Shared\RegistrationDeadline;
use App\Domain\Shared\TimeOfDay;
use App\Domain\Shared\WorkRegion;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RegistrationDeadlineTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, WorkRegion|null, string}>
     */
    public static function labels(): iterable
    {
        yield 'DIY tetap WIB' => ['2026-12-10', '23:59', WorkRegion::Diy, '10 Desember 2026 pukul 23.59 WIB'];
        yield 'Jawa Tengah tetap WIB' => ['2026-12-10', '16:00', WorkRegion::JawaTengah, '10 Desember 2026 pukul 16.00 WIB'];
        yield 'tanpa wilayah memakai WIB' => ['2026-12-10', '23:59', null, '10 Desember 2026 pukul 23.59 WIB'];
        yield 'Bali, tanggal bergeser' => ['2026-12-10', '23:59', WorkRegion::Bali, '11 Desember 2026 pukul 00.59 WITA (10 Desember 2026 pukul 23.59 WIB)'];
        yield 'Bali, tanggal tetap' => ['2026-12-10', '22:00', WorkRegion::Bali, '10 Desember 2026 pukul 23.00 WITA (10 Desember 2026 pukul 22.00 WIB)'];
        yield 'Bali, pergantian bulan' => ['2026-11-30', '23:30', WorkRegion::Bali, '1 Desember 2026 pukul 00.30 WITA (30 November 2026 pukul 23.30 WIB)'];
        yield 'Bali, pergantian tahun' => ['2026-12-31', '23:59', WorkRegion::Bali, '1 Januari 2027 pukul 00.59 WITA (31 Desember 2026 pukul 23.59 WIB)'];
    }

    #[DataProvider('labels')]
    public function test_closing_time_is_shown_in_region_timezone(string $date, string $time, ?WorkRegion $region, string $expected): void
    {
        $this->assertSame($expected, RegistrationDeadline::label(new DateTimeImmutable($date), TimeOfDay::fromString($time), $region));
    }

    public function test_deadline_moment_is_in_wib(): void
    {
        $moment = RegistrationDeadline::at(new DateTimeImmutable('2026-12-10'), TimeOfDay::endOfDay());

        $this->assertSame('2026-12-10T23:59:00+07:00', $moment->format(DATE_ATOM));
    }

    public function test_time_of_day_parsing(): void
    {
        $this->assertSame('23:59', TimeOfDay::fromString('23:59')->value());
        $this->assertSame('23:59', TimeOfDay::fromString('23.59')->value());
        $this->assertSame('07:05', TimeOfDay::fromString(' 07:05:00 ')->value());
        $this->assertSame('23.59', TimeOfDay::endOfDay()->label());

        foreach (['24:00', '7:5', '23:60', 'tengah malam', ''] as $invalid) {
            try {
                TimeOfDay::fromString($invalid, 'registration_closes_at');
                $this->fail("{$invalid} seharusnya ditolak.");
            } catch (InvalidTimeOfDay $exception) {
                $this->assertSame('registration_closes_at', $exception->field());
            }
        }
    }

    public function test_regions(): void
    {
        $this->assertSame(['WIB', 'WIB', 'WITA'], array_map(fn (WorkRegion $region): string => $region->timezoneLabel(), WorkRegion::cases()));
        $this->assertSame('Asia/Makassar', WorkRegion::Bali->timezone()->getName());
        $this->assertSame('Jawa Tengah', WorkRegion::JawaTengah->label());
    }
}
