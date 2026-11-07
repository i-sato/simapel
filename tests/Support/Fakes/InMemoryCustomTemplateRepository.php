<?php

namespace Tests\Support\Fakes;

use App\Application\Messaging\Contracts\CustomTemplateRepository;
use App\Domain\Messaging\CustomTemplate;

final class InMemoryCustomTemplateRepository implements CustomTemplateRepository
{
    /** @var array<int, CustomTemplate> */
    public array $templates = [];

    private int $nextId = 1;

    public function forOwner(int $trainingId, int $ownerId, bool $lock = false): array
    {
        return array_values(array_filter(
            $this->templates,
            fn (CustomTemplate $template): bool => $template->trainingId === $trainingId && $template->ownerId === $ownerId,
        ));
    }

    public function insert(CustomTemplate $template): int
    {
        $id = $this->nextId++;
        $this->templates[$id] = new CustomTemplate($id, $template->trainingId, $template->ownerId, $template->body);

        return $id;
    }

    public function update(CustomTemplate $template): void
    {
        $this->templates[(int) $template->id] = $template;
    }

    public function delete(array $ids): void
    {
        foreach ($ids as $id) {
            unset($this->templates[$id]);
        }
    }
}
