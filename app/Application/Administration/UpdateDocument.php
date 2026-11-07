<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Application\Administration\Contracts\AdministrativeDocumentRepository;
use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Administration\AdministrativeDocument;
use App\Domain\Administration\DocumentPermissions;
use App\Domain\Administration\DocumentStatus;
use App\Domain\Administration\InvalidDocument;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Shared\HttpUrl;
use DateTimeImmutable;

/**
 * Sekretaris yang ditugaskan, Ketua tim, atau Super Admin memperbarui status, nomor,
 * tanggal, dan link berkas dokumen.
 */
final readonly class UpdateDocument
{
    public function __construct(
        private DocumentAccess $access,
        private AdministrativeDocumentRepository $documents,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    public function handle(int $actorId, int $documentId, DocumentInput $input): bool
    {
        $status = DocumentStatus::tryFrom($input->status) ?? throw new InvalidDocument('Status tidak valid.', 'status');
        $date = self::date($input->date);
        $url = HttpUrl::fromNullable($input->fileUrl, 'file_url');

        return $this->transaction->run(function () use ($actorId, $documentId, $status, $input, $date, $url): bool {
            [$document, $training, $actor] = $this->access->load($actorId, $documentId);

            if (! DocumentPermissions::canUpdate($actor, $training, $document)) {
                DocumentAccess::deny();
            }

            $updated = $document->record($status, $input->number, $date, $url);
            $old = self::snapshot($document);
            $new = self::snapshot($updated);

            if ($old === $new) {
                return false;
            }

            $this->documents->save($updated, $actorId);

            $context = ['document' => $document->type->value];

            foreach ($new as $field => $value) {
                if ($old[$field] !== $value) {
                    $context['old_'.$field] = $old[$field];
                    $context['new_'.$field] = $value;
                }
            }

            $this->audit->record(new AuditEvent(
                type: AuditEventType::DocumentUpdated,
                description: sprintf('%s pelatihan "%s" diperbarui (%s).', $document->type->label(), $training->details->name, $updated->status->label()),
                actorId: $actorId,
                subjectType: 'training',
                subjectId: $training->id,
                context: $context,
            ));

            return true;
        });
    }

    /**
     * @return array{status: string, number: string|null, date: string|null, file_url: string|null}
     */
    private static function snapshot(AdministrativeDocument $document): array
    {
        return [
            'status' => $document->status->value,
            'number' => $document->number,
            'date' => $document->date?->format('Y-m-d'),
            'file_url' => $document->fileUrl?->value,
        ];
    }

    private static function date(?string $value): ?DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        if ($date === false || $date->format('Y-m-d') !== trim($value)) {
            throw new InvalidDocument('Format tanggal harus YYYY-MM-DD.', 'date');
        }

        return $date;
    }
}
