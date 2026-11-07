<?php

namespace Tests\Unit\Domain\Training;

use App\Domain\Training\InvalidTraining;
use App\Domain\Training\TrainingPhase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\TrainingBuilder;

final class TrainingScheduleTest extends TestCase
{
    /**
     * @return iterable<string, array{string|null, string|null, string|null, string|null, string}>
     */
    public static function inconsistent(): iterable
    {
        yield 'akhir pendaftaran sebelum awal' => ['2026-12-10', '2026-12-01', null, null, 'registration_end'];
        yield 'akhir pelatihan sebelum awal' => [null, null, '2026-12-17', '2026-12-15', 'training_end'];
        yield 'pelatihan mulai sebelum pendaftaran ditutup' => ['2026-12-01', '2026-12-10', '2026-12-09', '2026-12-12', 'training_start'];
        yield 'pelatihan mulai sebelum pendaftaran dibuka' => ['2026-12-05', null, '2026-12-01', null, 'training_start'];
    }

    #[DataProvider('inconsistent')]
    public function test_rejects_inconsistent_dates(?string $rs, ?string $re, ?string $ts, ?string $te, string $field): void
    {
        try {
            TrainingBuilder::schedule($rs, $re, $ts, $te);
            $this->fail('Jadwal tidak konsisten seharusnya ditolak.');
        } catch (InvalidTraining $exception) {
            $this->assertSame($field, $exception->field());
        }
    }

    public function test_partial_and_same_day_schedules_are_allowed(): void
    {
        $this->assertFalse(TrainingBuilder::schedule(null, null, null, null)->isComplete());
        $this->assertFalse(TrainingBuilder::schedule('2026-12-01', null, null, null)->isComplete());

        $sameDay = TrainingBuilder::schedule('2026-12-01', '2026-12-01', '2026-12-01', '2026-12-01');
        $this->assertTrue($sameDay->isComplete());
        $this->assertSame(0, $sameDay->preparationDays());
    }

    /**
     * @return iterable<string, array{string, TrainingPhase}>
     */
    public static function phases(): iterable
    {
        yield 'sebelum dibuka' => ['2026-11-30 23:59', TrainingPhase::AkanDibuka];
        yield 'hari pertama pendaftaran' => ['2026-12-01 00:00', TrainingPhase::Pendaftaran];
        yield 'hari terakhir pendaftaran' => ['2026-12-10 23:59', TrainingPhase::Pendaftaran];
        yield 'setelah pendaftaran ditutup' => ['2026-12-11 08:00', TrainingPhase::PenetapanPeserta];
        yield 'hari pertama pelatihan' => ['2026-12-15 07:00', TrainingPhase::Pelaksanaan];
        yield 'hari terakhir pelatihan' => ['2026-12-17 22:00', TrainingPhase::Pelaksanaan];
        yield 'setelah selesai' => ['2026-12-18 00:00', TrainingPhase::Selesai];
    }

    #[DataProvider('phases')]
    public function test_phase_is_derived_from_dates(string $now, TrainingPhase $expected): void
    {
        $this->assertSame($expected, TrainingBuilder::schedule()->phaseOn(new DateTimeImmutable($now)));
    }

    public function test_incomplete_schedule_has_no_phase(): void
    {
        $this->assertNull(TrainingBuilder::schedule('2026-12-01', '2026-12-10', null, null)->phaseOn(new DateTimeImmutable('2026-12-05')));
    }

    public function test_registration_end_and_preparation_days(): void
    {
        $schedule = TrainingBuilder::schedule();

        $this->assertFalse($schedule->registrationEndedOn(new DateTimeImmutable('2026-12-10 23:00')));
        $this->assertTrue($schedule->registrationEndedOn(new DateTimeImmutable('2026-12-11 00:00')));
        $this->assertSame(5, $schedule->preparationDays());
        $this->assertNull(TrainingBuilder::schedule(null, null, null, null)->preparationDays());
        $this->assertFalse(TrainingBuilder::schedule(null, null, null, null)->registrationEndedOn(new DateTimeImmutable('2030-01-01')));
        $this->assertTrue($schedule->equals(TrainingBuilder::schedule()));
        $this->assertFalse($schedule->equals(TrainingBuilder::schedule(trainingEnd: '2026-12-18')));
    }

    public function test_assignment_range_runs_from_registration_start_to_training_end(): void
    {
        $this->assertSame('1–17 Des', TrainingBuilder::schedule()->assignmentRange()?->label());
        $this->assertNull(TrainingBuilder::schedule(null, '2026-12-10', '2026-12-15', '2026-12-17')->assignmentRange());
        $this->assertNull(TrainingBuilder::schedule('2026-12-01', '2026-12-10', '2026-12-15', null)->assignmentRange());
    }
}
