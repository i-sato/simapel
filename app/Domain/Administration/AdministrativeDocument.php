<?php

declare(strict_types=1);

namespace App\Domain\Administration;

use App\Domain\Organization\TeamRole;
use App\Domain\Shared\HttpUrl;
use DateTimeImmutable;

/**
 * Dokumen BAP/SK/ST satu pelatihan Final. Berkas tetap di penyimpanan kantor;
 * SIMAPEL hanya mencatat status, nomor, tanggal, dan link-nya (tanpa unggah).
 */
final readonly class AdministrativeDocument
{
    public const MAX_NUMBER_LENGTH = 100;

    private function __construct(
        public ?int $id,
        public int $trainingId,
        public DocumentType $type,
        public DocumentStatus $status,
        public ?string $number,
        public ?DateTimeImmutable $date,
        public ?HttpUrl $fileUrl,
        public ?int $assigneeId,
    ) {}

    public static function open(int $trainingId, DocumentType $type): self
    {
        return new self(null, $trainingId, $type, DocumentStatus::Belum, null, null, null, null);
    }

    public static function restore(
        int $id,
        int $trainingId,
        DocumentType $type,
        DocumentStatus $status,
        ?string $number,
        ?DateTimeImmutable $date,
        ?HttpUrl $fileUrl,
        ?int $assigneeId,
    ): self {
        return new self($id, $trainingId, $type, $status, $number, $date, $fileUrl, $assigneeId);
    }

    /**
     * Status Selesai mewajibkan nomor, tanggal, dan link berkas.
     *
     * @throws InvalidDocument
     */
    public function record(DocumentStatus $status, ?string $number, ?DateTimeImmutable $date, ?HttpUrl $fileUrl): self
    {
        $number = $number === null ? null : trim($number);
        $number = $number === '' ? null : $number;

        if ($number !== null && mb_strlen($number) > self::MAX_NUMBER_LENGTH) {
            throw new InvalidDocument(sprintf('Nomor dokumen maksimal %d karakter.', self::MAX_NUMBER_LENGTH), 'number');
        }

        if ($status === DocumentStatus::Selesai) {
            foreach (['number' => [$number, 'nomor'], 'date' => [$date, 'tanggal'], 'file_url' => [$fileUrl, 'link berkas']] as $field => [$value, $label]) {
                if ($value === null) {
                    throw new InvalidDocument(sprintf('%s Selesai wajib memiliki %s.', $this->type->label(), $label), $field);
                }
            }
        }

        return new self($this->id, $this->trainingId, $this->type, $status, $number, $date?->setTime(0, 0), $fileUrl, $this->assigneeId);
    }

    /**
     * Ketua mendelegasikan dokumen ke Sekretaris timnya (K3); null mencabut delegasi.
     *
     * @param  TeamRole|null  $assigneeTeamRole  peran penerima pada tim kerja pelatihan
     *
     * @throws InvalidDocument
     */
    public function delegateTo(?int $assigneeId, ?TeamRole $assigneeTeamRole): self
    {
        if ($assigneeId !== null && $assigneeTeamRole !== TeamRole::Sekretaris) {
            throw new InvalidDocument('Dokumen hanya dapat didelegasikan ke Sekretaris tim kerja pelatihan ini.', 'assignee_id');
        }

        return new self($this->id, $this->trainingId, $this->type, $this->status, $this->number, $this->date, $this->fileUrl, $assigneeId);
    }

    public function isDone(): bool
    {
        return $this->status === DocumentStatus::Selesai;
    }
}
