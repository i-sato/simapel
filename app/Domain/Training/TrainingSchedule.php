<?php

declare(strict_types=1);

namespace App\Domain\Training;

use App\Domain\Shared\DateRange;
use DateTimeImmutable;

/**
 * Periode pendaftaran dan pelaksanaan (tanggal saja, inklusif). Draft boleh belum lengkap,
 * tetapi tanggal yang sudah diisi harus konsisten.
 */
final readonly class TrainingSchedule
{
    private function __construct(
        public ?DateTimeImmutable $registrationStart,
        public ?DateTimeImmutable $registrationEnd,
        public ?DateTimeImmutable $trainingStart,
        public ?DateTimeImmutable $trainingEnd,
    ) {}

    /**
     * @throws InvalidTraining
     */
    public static function of(
        ?DateTimeImmutable $registrationStart,
        ?DateTimeImmutable $registrationEnd,
        ?DateTimeImmutable $trainingStart,
        ?DateTimeImmutable $trainingEnd,
    ): self {
        $schedule = new self(
            self::day($registrationStart),
            self::day($registrationEnd),
            self::day($trainingStart),
            self::day($trainingEnd),
        );

        if (self::after($schedule->registrationStart, $schedule->registrationEnd)) {
            throw new InvalidTraining('Akhir pendaftaran tidak boleh sebelum awal pendaftaran.', 'registration_end');
        }

        if (self::after($schedule->trainingStart, $schedule->trainingEnd)) {
            throw new InvalidTraining('Akhir pelatihan tidak boleh sebelum awal pelatihan.', 'training_end');
        }

        if (self::after($schedule->registrationEnd, $schedule->trainingStart)) {
            throw new InvalidTraining('Pelatihan tidak boleh dimulai sebelum pendaftaran ditutup.', 'training_start');
        }

        if (self::after($schedule->registrationStart, $schedule->trainingStart)) {
            throw new InvalidTraining('Pelatihan tidak boleh dimulai sebelum pendaftaran dibuka.', 'training_start');
        }

        return $schedule;
    }

    public static function empty(): self
    {
        return new self(null, null, null, null);
    }

    public function isComplete(): bool
    {
        return $this->registrationStart !== null && $this->registrationEnd !== null
            && $this->trainingStart !== null && $this->trainingEnd !== null;
    }

    /**
     * Status waktu pada tanggal tertentu; null bila jadwal belum lengkap.
     */
    public function phaseOn(DateTimeImmutable $now): ?TrainingPhase
    {
        if (! $this->isComplete()) {
            return null;
        }

        $today = self::day($now);

        return match (true) {
            $today < $this->registrationStart => TrainingPhase::AkanDibuka,
            $today <= $this->registrationEnd => TrainingPhase::Pendaftaran,
            $today < $this->trainingStart => TrainingPhase::PenetapanPeserta,
            $today <= $this->trainingEnd => TrainingPhase::Pelaksanaan,
            default => TrainingPhase::Selesai,
        };
    }

    /**
     * Pendaftaran dianggap berakhir sehari setelah tanggal akhir pendaftaran.
     */
    public function registrationEndedOn(DateTimeImmutable $now): bool
    {
        return $this->registrationEnd !== null && self::day($now) > $this->registrationEnd;
    }

    /**
     * Jumlah hari dari penutupan pendaftaran sampai pelatihan dimulai (H+n).
     */
    public function preparationDays(): ?int
    {
        if ($this->registrationEnd === null || $this->trainingStart === null) {
            return null;
        }

        return (int) $this->registrationEnd->diff($this->trainingStart)->days;
    }

    /**
     * Rentang beban kerja penugasan: awal pendaftaran sampai akhir pelatihan (K8).
     */
    public function assignmentRange(): ?DateRange
    {
        return $this->registrationStart === null || $this->trainingEnd === null
            ? null
            : new DateRange($this->registrationStart, $this->trainingEnd);
    }

    public function equals(self $other): bool
    {
        return self::same($this->registrationStart, $other->registrationStart)
            && self::same($this->registrationEnd, $other->registrationEnd)
            && self::same($this->trainingStart, $other->trainingStart)
            && self::same($this->trainingEnd, $other->trainingEnd);
    }

    private static function day(?DateTimeImmutable $date): ?DateTimeImmutable
    {
        return $date?->setTime(0, 0);
    }

    private static function after(?DateTimeImmutable $first, ?DateTimeImmutable $second): bool
    {
        return $first !== null && $second !== null && $first > $second;
    }

    private static function same(?DateTimeImmutable $first, ?DateTimeImmutable $second): bool
    {
        return $first?->format('Y-m-d') === $second?->format('Y-m-d');
    }
}
