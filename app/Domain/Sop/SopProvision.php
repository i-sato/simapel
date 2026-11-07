<?php

declare(strict_types=1);

namespace App\Domain\Sop;

use App\Domain\Organization\WorkTeamCode;
use DateTimeImmutable;

/**
 * Ketentuan SOP berversi untuk satu tim kerja pada satu tahun anggaran (ADR 0010):
 * acuan SOP dan tenggat administratif. Hanya Draf yang dapat diubah; revisi
 * dibuat sebagai versi baru.
 */
final readonly class SopProvision
{
    private function __construct(
        public ?int $id,
        public WorkTeamCode $team,
        public int $fiscalYear,
        public int $version,
        public SopProvisionStatus $status,
        public SopReference $sop,
        public DateTimeImmutable $effectiveFrom,
        public ?DateTimeImmutable $supersededFrom,
        public AdministrativeDeadlines $deadlines,
        public ?string $note,
    ) {}

    /**
     * @throws InvalidSopProvision
     */
    public static function draft(
        WorkTeamCode $team,
        int $fiscalYear,
        int $version,
        SopReference $sop,
        DateTimeImmutable $effectiveFrom,
        AdministrativeDeadlines $deadlines,
        ?string $note = null,
    ): self {
        self::assertWithinYear($effectiveFrom, $fiscalYear);

        return new self(null, $team, $fiscalYear, $version, SopProvisionStatus::Draf, $sop, $effectiveFrom, null, $deadlines, self::cleanNote($note));
    }

    /**
     * Memulihkan dari penyimpanan tanpa validasi ulang aturan pembuatan.
     */
    public static function restore(
        int $id,
        WorkTeamCode $team,
        int $fiscalYear,
        int $version,
        SopProvisionStatus $status,
        SopReference $sop,
        DateTimeImmutable $effectiveFrom,
        ?DateTimeImmutable $supersededFrom,
        AdministrativeDeadlines $deadlines,
        ?string $note,
    ): self {
        return new self($id, $team, $fiscalYear, $version, $status, $sop, $effectiveFrom, $supersededFrom, $deadlines, $note);
    }

    public function withId(int $id): self
    {
        return new self($id, $this->team, $this->fiscalYear, $this->version, $this->status, $this->sop, $this->effectiveFrom, $this->supersededFrom, $this->deadlines, $this->note);
    }

    /**
     * @throws SopProvisionLocked|InvalidSopProvision
     */
    public function revise(SopReference $sop, DateTimeImmutable $effectiveFrom, AdministrativeDeadlines $deadlines, ?string $note): self
    {
        $this->assertDraft();
        self::assertWithinYear($effectiveFrom, $this->fiscalYear);

        return new self($this->id, $this->team, $this->fiscalYear, $this->version, $this->status, $sop, $effectiveFrom, null, $deadlines, self::cleanNote($note));
    }

    /**
     * @throws SopProvisionLocked|InvalidSopProvision
     */
    public function activate(): self
    {
        $this->assertDraft();

        if (! $this->sop->isComplete()) {
            throw new InvalidSopProvision('Nomor SOP dan link SOP wajib diisi sebelum ketentuan diberlakukan.', 'sop_url');
        }

        return new self($this->id, $this->team, $this->fiscalYear, $this->version, SopProvisionStatus::Berlaku, $this->sop, $this->effectiveFrom, null, $this->deadlines, $this->note);
    }

    public function supersede(DateTimeImmutable $from): self
    {
        return new self($this->id, $this->team, $this->fiscalYear, $this->version, SopProvisionStatus::Diganti, $this->sop, $this->effectiveFrom, $from, $this->deadlines, $this->note);
    }

    /**
     * @throws SopProvisionLocked
     */
    public function assertDraft(): void
    {
        if ($this->status !== SopProvisionStatus::Draf) {
            throw new SopProvisionLocked;
        }
    }

    /**
     * Draf baru untuk tahun anggaran berikutnya, mulai berlaku 1 Januari.
     */
    public function copyForYear(int $fiscalYear, int $version): self
    {
        return new self(
            null,
            $this->team,
            $fiscalYear,
            $version,
            SopProvisionStatus::Draf,
            $this->sop,
            new DateTimeImmutable(sprintf('%d-01-01', $fiscalYear)),
            null,
            $this->deadlines,
            sprintf('Disalin dari versi %d tahun %d. Periksa kembali terhadap SOP yang berlaku.', $this->version, $this->fiscalYear),
        );
    }

    private static function assertWithinYear(DateTimeImmutable $date, int $fiscalYear): void
    {
        if ((int) $date->format('Y') !== $fiscalYear) {
            throw new InvalidSopProvision(sprintf('Tanggal mulai berlaku harus berada pada tahun anggaran %d.', $fiscalYear), 'effective_from');
        }
    }

    private static function cleanNote(?string $note): ?string
    {
        $note = $note === null ? null : trim($note);

        if ($note !== null && mb_strlen($note) > 2000) {
            throw new InvalidSopProvision('Catatan maksimal 2000 karakter.', 'note');
        }

        return $note === '' ? null : $note;
    }
}
