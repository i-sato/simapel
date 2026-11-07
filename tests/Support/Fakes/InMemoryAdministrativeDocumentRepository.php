<?php

namespace Tests\Support\Fakes;

use App\Application\Administration\Contracts\AdministrativeDocumentRepository;
use App\Domain\Administration\AdministrativeDocument;
use App\Domain\Administration\DocumentType;

final class InMemoryAdministrativeDocumentRepository implements AdministrativeDocumentRepository
{
    /** @var array<int, AdministrativeDocument> */
    public array $documents = [];

    private int $nextId = 1;

    public function forTraining(int $trainingId): array
    {
        return array_values(array_filter($this->documents, fn (AdministrativeDocument $document): bool => $document->trainingId === $trainingId));
    }

    public function findForUpdate(int $id): ?AdministrativeDocument
    {
        return $this->documents[$id] ?? null;
    }

    public function openMissing(int $trainingId): void
    {
        $existing = array_map(fn (AdministrativeDocument $document): DocumentType => $document->type, $this->forTraining($trainingId));

        foreach (DocumentType::cases() as $type) {
            if (! in_array($type, $existing, true)) {
                $id = $this->nextId++;
                $open = AdministrativeDocument::open($trainingId, $type);
                $this->documents[$id] = AdministrativeDocument::restore($id, $trainingId, $type, $open->status, null, null, null, null);
            }
        }
    }

    public function save(AdministrativeDocument $document, int $actorId): void
    {
        $this->documents[(int) $document->id] = $document;
    }

    public function pendingAssignedTo(int $employeeId): array
    {
        return array_values(array_filter($this->documents, fn (AdministrativeDocument $document): bool => $document->assigneeId === $employeeId && ! $document->isDone()));
    }
}
