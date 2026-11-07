<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Application\Administration\Contracts\AdministrativeDocumentRepository;
use App\Application\Training\Contracts\TrainingRepository;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\Exceptions\TrainingNotFound;
use App\Application\Training\TrainingActorProvider;
use App\Domain\Administration\AdministrativeDocument;
use App\Domain\Administration\DocumentPermissions;
use App\Domain\Training\Training;
use App\Domain\Training\TrainingActor;

/**
 * Memuat dokumen beserta pelatihan dan pelaku, lalu memeriksa haknya.
 */
final readonly class DocumentAccess
{
    public function __construct(
        private AdministrativeDocumentRepository $documents,
        private TrainingRepository $trainings,
        private TrainingActorProvider $actors,
    ) {}

    /**
     * @return array{AdministrativeDocument, Training, TrainingActor}
     *
     * @throws TrainingNotFound
     */
    public function load(int $actorId, int $documentId): array
    {
        $document = $this->documents->findForUpdate($documentId) ?? throw new TrainingNotFound;
        $training = $this->trainings->find($document->trainingId) ?? throw new TrainingNotFound;

        return [$document, $training, $this->actors->forUser($actorId)];
    }

    public function canDelegate(int $actorId, int $trainingId): bool
    {
        $training = $this->trainings->find($trainingId);

        return $training !== null && DocumentPermissions::canDelegate($this->actors->forUser($actorId), $training);
    }

    /**
     * @return array<int, bool> documentId => boleh diperbarui
     */
    public function updatableIn(int $actorId, int $trainingId): array
    {
        $training = $this->trainings->find($trainingId);

        if ($training === null) {
            return [];
        }

        $actor = $this->actors->forUser($actorId);
        $result = [];

        foreach ($this->documents->forTraining($trainingId) as $document) {
            $result[(int) $document->id] = DocumentPermissions::canUpdate($actor, $training, $document);
        }

        return $result;
    }

    /**
     * @throws TrainingAccessDenied
     */
    public static function deny(): never
    {
        throw new TrainingAccessDenied;
    }
}
