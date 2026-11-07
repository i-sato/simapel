<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Administration\Contracts\AdministrativeDocumentRepository;
use App\Domain\Administration\AdministrativeDocument;
use App\Domain\Administration\DocumentStatus;
use App\Domain\Administration\DocumentType;
use App\Domain\Shared\HttpUrl;
use App\Models\AdministrativeDocumentRecord;

final class EloquentAdministrativeDocumentRepository implements AdministrativeDocumentRepository
{
    private const ORDER = ['bap' => 0, 'sk' => 1, 'st' => 2];

    public function forTraining(int $trainingId): array
    {
        $documents = AdministrativeDocumentRecord::query()->where('training_id', $trainingId)->get()
            ->map(fn (AdministrativeDocumentRecord $record): AdministrativeDocument => $this->toDomain($record))
            ->all();

        usort($documents, fn (AdministrativeDocument $a, AdministrativeDocument $b): int => self::ORDER[$a->type->value] <=> self::ORDER[$b->type->value]);

        return $documents;
    }

    public function findForUpdate(int $id): ?AdministrativeDocument
    {
        $record = AdministrativeDocumentRecord::query()->whereKey($id)->lockForUpdate()->first();

        return $record === null ? null : $this->toDomain($record);
    }

    public function openMissing(int $trainingId): void
    {
        foreach (DocumentType::cases() as $type) {
            AdministrativeDocumentRecord::query()->firstOrCreate(
                ['training_id' => $trainingId, 'type' => $type],
                ['status' => DocumentStatus::Belum],
            );
        }
    }

    public function save(AdministrativeDocument $document, int $actorId): void
    {
        AdministrativeDocumentRecord::query()->findOrFail($document->id)->forceFill([
            'status' => $document->status,
            'number' => $document->number,
            'document_date' => $document->date?->format('Y-m-d'),
            'file_url' => $document->fileUrl?->value,
            'assignee_id' => $document->assigneeId,
            'updated_by' => $actorId,
        ])->save();
    }

    public function pendingAssignedTo(int $employeeId): array
    {
        return array_values(AdministrativeDocumentRecord::query()
            ->where('assignee_id', $employeeId)
            ->where('status', '!=', DocumentStatus::Selesai)
            ->orderBy('training_id')
            ->orderBy('id')
            ->get()
            ->map(fn (AdministrativeDocumentRecord $record): AdministrativeDocument => $this->toDomain($record))
            ->all());
    }

    private function toDomain(AdministrativeDocumentRecord $record): AdministrativeDocument
    {
        return AdministrativeDocument::restore(
            $record->id,
            $record->training_id,
            $record->type,
            $record->status,
            $record->number,
            $record->document_date,
            HttpUrl::fromNullable($record->file_url, 'file_url'),
            $record->assignee_id,
        );
    }
}
