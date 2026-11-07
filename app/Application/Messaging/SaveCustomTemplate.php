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
use App\Domain\Messaging\InvalidMessageTemplate;
use App\Domain\Messaging\TemplateBody;
use App\Domain\Messaging\TemplateSet;

/**
 * Membuat atau mengubah template kustom (maks. 3), lalu menggabungkan template yang
 * identik dengan default atau dengan template lain.
 */
final readonly class SaveCustomTemplate
{
    public function __construct(
        private TrainingMessages $messages,
        private CustomTemplateRepository $templates,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    /**
     * @param  int|null  $templateId  null untuk template baru
     * @return int|null id template yang tersimpan, atau null bila digabung dengan default/template lain
     *
     * @throws TrainingAccessDenied|TrainingNotFound|InvalidMessageTemplate
     */
    public function handle(int $actorId, int $trainingId, ?int $templateId, string $body): ?int
    {
        $validated = new TemplateBody($body);

        return $this->transaction->run(function () use ($actorId, $trainingId, $templateId, $validated): ?int {
            $training = $this->messages->authorized($actorId, $trainingId, lock: true);
            $existing = $this->templates->forOwner($trainingId, $actorId, lock: true);

            if ($templateId === null) {
                TemplateSet::assertCanAdd($existing);
                $id = $this->templates->insert(new CustomTemplate(null, $trainingId, $actorId, $validated));
            } else {
                if (! in_array($templateId, TrainingMessages::ids($existing), true)) {
                    throw new TrainingAccessDenied;
                }

                $id = $templateId;
                $this->templates->update(new CustomTemplate($id, $trainingId, $actorId, $validated));
            }

            $removed = TemplateSet::redundant($this->templates->forOwner($trainingId, $actorId, lock: true), $this->messages->defaultBody($training));
            $this->templates->delete($removed);
            $kept = in_array($id, $removed, true) ? null : $id;

            $this->audit->record(new AuditEvent(
                type: AuditEventType::MessageTemplateSaved,
                description: sprintf('Template WA pelatihan "%s" disimpan%s.', $training->details->name, $kept === null ? ' (identik, digabung)' : ''),
                actorId: $actorId,
                subjectType: 'training',
                subjectId: $trainingId,
                context: ['template_id' => $id, 'length' => mb_strlen($validated->value), 'merged' => $kept === null, 'removed_count' => count($removed)],
            ));

            return $kept;
        });
    }
}
