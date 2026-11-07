<?php

declare(strict_types=1);

namespace App\Domain\Training;

use DateTimeImmutable;

/**
 * Pelatihan dengan alur Draft → Final (K5, ADR 0009).
 *
 * - Draft boleh parsial; tanggal dan angka yang diisi tetap divalidasi.
 * - Finalisasi mewajibkan data lengkap, menyimpan salinan ketentuan SOP, dan tidak dapat dibatalkan.
 * - Pelatihan Final masih dapat dilengkapi (mis. link daftar peserta) asalkan tetap lengkap;
 *   tim kerja dan salinan ketentuan tidak berubah.
 */
final readonly class Training
{
    private function __construct(
        public ?int $id,
        public int $teamId,
        public TrainingStatus $status,
        public TrainingDetails $details,
        public ?int $learningPathId,
        public ?SopSnapshot $sopSnapshot,
        public ?DateTimeImmutable $finalizedAt,
    ) {}

    /**
     * @throws InvalidTraining
     */
    public static function draft(int $teamId, TrainingDetails $details, ?int $learningPathId, DateTimeImmutable $now): self
    {
        self::assertParticipantListAllowed($details, null, $now);
        self::assertPathHasLevel($details, $learningPathId);

        return new self(null, $teamId, TrainingStatus::Draft, $details, $learningPathId, null, null);
    }

    public static function restore(
        int $id,
        int $teamId,
        TrainingStatus $status,
        TrainingDetails $details,
        ?int $learningPathId,
        ?SopSnapshot $sopSnapshot,
        ?DateTimeImmutable $finalizedAt,
    ): self {
        return new self($id, $teamId, $status, $details, $learningPathId, $sopSnapshot, $finalizedAt);
    }

    public function withId(int $id): self
    {
        return new self($id, $this->teamId, $this->status, $this->details, $this->learningPathId, $this->sopSnapshot, $this->finalizedAt);
    }

    /**
     * @throws InvalidTraining|TrainingIncomplete
     */
    public function revise(int $teamId, TrainingDetails $details, ?int $learningPathId, DateTimeImmutable $now): self
    {
        if ($this->isFinal()) {
            if ($teamId !== $this->teamId) {
                throw new InvalidTraining('Tim kerja pelatihan Final tidak dapat diubah.', 'work_team_id');
            }

            $missing = $details->missingForFinal();

            if ($missing !== []) {
                throw new TrainingIncomplete($missing);
            }
        }

        self::assertParticipantListAllowed($details, $this->details, $now);
        self::assertPathHasLevel($details, $learningPathId);

        return new self($this->id, $teamId, $this->status, $details, $learningPathId, $this->sopSnapshot, $this->finalizedAt);
    }

    /**
     * @param  SopSnapshot|null  $snapshot  null bila tim belum punya ketentuan Berlaku sama sekali
     *
     * @throws TrainingLocked|TrainingIncomplete
     */
    public function finalize(?SopSnapshot $snapshot, DateTimeImmutable $now): self
    {
        if ($this->isFinal()) {
            throw new TrainingLocked('Pelatihan sudah Final.');
        }

        $missing = $this->details->missingForFinal();

        if ($missing !== []) {
            throw new TrainingIncomplete($missing);
        }

        return new self($this->id, $this->teamId, TrainingStatus::Final, $this->details, $this->learningPathId, $snapshot, $now);
    }

    public function isFinal(): bool
    {
        return $this->status === TrainingStatus::Final;
    }

    public function phaseOn(DateTimeImmutable $now): ?TrainingPhase
    {
        return $this->details->schedule->phaseOn($now);
    }

    /**
     * Link daftar peserta baru dapat diisi atau diubah setelah periode pendaftaran berakhir.
     *
     * @throws InvalidTraining
     */
    private static function assertParticipantListAllowed(TrainingDetails $details, ?TrainingDetails $current, DateTimeImmutable $now): void
    {
        $new = $details->participantListUrl?->value;

        if ($new === null || $new === $current?->participantListUrl?->value) {
            return;
        }

        if (! $details->schedule->registrationEndedOn($now)) {
            throw new InvalidTraining('Link daftar peserta baru dapat diisi setelah periode pendaftaran berakhir.', 'participant_list_url');
        }
    }

    /**
     * @throws InvalidTraining
     */
    private static function assertPathHasLevel(TrainingDetails $details, ?int $learningPathId): void
    {
        if ($learningPathId !== null && $details->level === null) {
            throw new InvalidTraining('Pilih level pelatihan untuk menempatkannya dalam learning path.', 'level');
        }
    }
}
