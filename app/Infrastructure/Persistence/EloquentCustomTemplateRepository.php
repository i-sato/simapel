<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Messaging\Contracts\CustomTemplateRepository;
use App\Domain\Messaging\CustomTemplate;
use App\Domain\Messaging\TemplateBody;
use App\Models\MessageTemplateRecord;

final class EloquentCustomTemplateRepository implements CustomTemplateRepository
{
    public function forOwner(int $trainingId, int $ownerId, bool $lock = false): array
    {
        return array_values(MessageTemplateRecord::query()
            ->where('training_id', $trainingId)
            ->where('user_id', $ownerId)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->orderBy('id')
            ->get()
            ->map(fn (MessageTemplateRecord $record): CustomTemplate => new CustomTemplate(
                $record->id,
                $record->training_id,
                $record->user_id,
                new TemplateBody($record->body),
            ))
            ->all());
    }

    public function insert(CustomTemplate $template): int
    {
        $record = MessageTemplateRecord::query()->create([
            'training_id' => $template->trainingId,
            'user_id' => $template->ownerId,
            'body' => $template->body->value,
        ]);

        return (int) $record->getKey();
    }

    public function update(CustomTemplate $template): void
    {
        MessageTemplateRecord::query()
            ->whereKey($template->id)
            ->where('user_id', $template->ownerId)
            ->update(['body' => $template->body->value]);
    }

    public function delete(array $ids): void
    {
        if ($ids !== []) {
            MessageTemplateRecord::query()->whereKey($ids)->delete();
        }
    }
}
