<?php

declare(strict_types=1);

namespace App\Application\Messaging;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Messaging\Contracts\CustomTemplateRepository;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\Exceptions\TrainingNotFound;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Messaging\CustomTemplate;
use App\Domain\Messaging\TemplateBody;
use App\Domain\Messaging\TemplateSet;

/**
 * Mengembalikan template kustom ke default. Karena menjadi identik dengan default,
 * template tersebut digabung (dihapus) sehingga tersisa template utama.
 */
final readonly class ResetCustomTemplate
{
    public function __construct(
        private TrainingMessages $messages,
        private CustomTemplateRepository $templates,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    /**
     * @throws TrainingAccessDenied|TrainingNotFound
     */
    public function handle(int $actorId, int $trainingId, int $templateId): void
    {
        $this->transaction->run(function () use ($actorId, $trainingId, $templateId): void {
            $training = $this->messages->authorized($actorId, $trainingId, lock: true);
            $existing = $this->templates->forOwner($trainingId, $actorId, lock: true);

            if (! in_array($templateId, TrainingMessages::ids($existing), true)) {
                throw new TrainingAccessDenied;
            }

            $default = $this->messages->defaultBody($training);
            $this->templates->update(new CustomTemplate($templateId, $trainingId, $actorId, new TemplateBody($default)));
            $removed = TemplateSet::redundant($this->templates->forOwner($trainingId, $actorId, lock: true), $default);
            $this->templates->delete($removed);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::MessageTemplateReset,
                description: sprintf('Template WA pelatihan "%s" di-reset ke default.', $training->details->name),
                actorId: $actorId,
                subjectType: 'training',
                subjectId: $trainingId,
                context: ['template_id' => $templateId, 'removed_count' => count($removed)],
            ));
        });
    }
}
