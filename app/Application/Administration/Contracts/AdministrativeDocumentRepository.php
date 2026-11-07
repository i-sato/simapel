<?php

declare(strict_types=1);

namespace App\Application\Administration\Contracts;

use App\Domain\Administration\AdministrativeDocument;

interface AdministrativeDocumentRepository
{
    /**
     * @return list<AdministrativeDocument> urut BAP, SK, ST
     */
    public function forTraining(int $trainingId): array;

    public function findForUpdate(int $id): ?AdministrativeDocument;

    /**
     * Membuat dokumen BAP/SK/ST yang belum ada untuk pelatihan (idempoten).
     */
    public function openMissing(int $trainingId): void;

    public function save(AdministrativeDocument $document, int $actorId): void;

    /**
     * Dokumen yang didelegasikan ke pegawai dan belum Selesai.
     *
     * @return list<AdministrativeDocument>
     */
    public function pendingAssignedTo(int $employeeId): array;
}
