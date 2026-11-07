<?php

declare(strict_types=1);

namespace App\Application\Messaging;

use App\Application\Messaging\Contracts\CustomTemplateRepository;
use App\Application\Training\Contracts\TrainingRepository;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\Exceptions\TrainingNotFound;
use App\Application\Training\TrainingActorProvider;
use App\Domain\Messaging\CustomTemplate;
use App\Domain\Messaging\DefaultTemplate;
use App\Domain\Messaging\TemplateRenderer;
use App\Domain\Training\Training;
use App\Domain\Training\TrainingPermissions;

/**
 * Template WA satu pelatihan untuk seorang anggota: default + template kustomnya,
 * masing-masing dalam bentuk placeholder dan hasil render.
 */
final readonly class TrainingMessages
{
    public function __construct(
        private TrainingRepository $trainings,
        private CustomTemplateRepository $templates,
        private TrainingActorProvider $actors,
        private TrainingMessageValues $values,
    ) {}

    /**
     * @return array{default: array{body: string, text: string}, custom: list<array{id: int, body: string, text: string}>}
     *
     * @throws TrainingAccessDenied|TrainingNotFound
     */
    public function for(int $actorId, int $trainingId): array
    {
        $training = $this->authorized($actorId, $trainingId);
        $values = $this->values->for($training);
        $default = DefaultTemplate::body($this->values->hasLearningPath($training));
        $custom = [];

        foreach ($this->templates->forOwner($trainingId, $actorId) as $template) {
            $custom[] = [
                'id' => (int) $template->id,
                'body' => $template->body->value,
                'text' => TemplateRenderer::render($template->body->value, $values),
            ];
        }

        return [
            'default' => ['body' => $default, 'text' => TemplateRenderer::render($default, $values)],
            'custom' => $custom,
        ];
    }

    /**
     * @throws TrainingAccessDenied|TrainingNotFound
     */
    public function authorized(int $actorId, int $trainingId, bool $lock = false): Training
    {
        $training = ($lock ? $this->trainings->findForUpdate($trainingId) : $this->trainings->find($trainingId))
            ?? throw new TrainingNotFound;

        if (! TrainingPermissions::canUseMessageTemplates($this->actors->forUser($actorId), $training)) {
            throw new TrainingAccessDenied;
        }

        return $training;
    }

    public function defaultBody(Training $training): string
    {
        return DefaultTemplate::body($this->values->hasLearningPath($training));
    }

    /**
     * @param  list<CustomTemplate>  $templates
     * @return list<int>
     */
    public static function ids(array $templates): array
    {
        return array_map(fn (CustomTemplate $template): int => (int) $template->id, $templates);
    }
}
