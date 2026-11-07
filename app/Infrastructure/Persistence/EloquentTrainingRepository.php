<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Training\Contracts\TrainingRepository;
use App\Domain\Shared\HttpUrl;
use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Training\InvalidTraining;
use App\Domain\Training\SopSnapshot;
use App\Domain\Training\Training;
use App\Domain\Training\TrainingDetails;
use App\Domain\Training\TrainingLevel;
use App\Domain\Training\TrainingSchedule;
use App\Models\TrainingRecord;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentTrainingRepository implements TrainingRepository
{
    public function find(int $id): ?Training
    {
        $record = TrainingRecord::query()->find($id);

        return $record === null ? null : $this->toDomain($record);
    }

    public function findForUpdate(int $id): ?Training
    {
        $record = TrainingRecord::query()->whereKey($id)->lockForUpdate()->first();

        return $record === null ? null : $this->toDomain($record);
    }

    public function insert(Training $training, int $actorId): int
    {
        try {
            $record = TrainingRecord::query()->create($this->attributes($training) + ['created_by' => $actorId, 'updated_by' => $actorId]);
        } catch (UniqueConstraintViolationException) {
            throw $this->levelTaken();
        }

        return (int) $record->getKey();
    }

    public function save(Training $training, int $actorId): void
    {
        $attributes = $this->attributes($training) + ['updated_by' => $actorId];

        if ($training->isFinal()) {
            $attributes['finalized_by'] = TrainingRecord::query()->whereKey($training->id)->value('finalized_by') ?? $actorId;
        }

        try {
            TrainingRecord::query()->findOrFail($training->id)->forceFill($attributes)->save();
        } catch (UniqueConstraintViolationException) {
            throw $this->levelTaken();
        }
    }

    public function levelOccupant(int $learningPathId, TrainingLevel $level): ?int
    {
        $id = TrainingRecord::query()->where('learning_path_id', $learningPathId)->where('level', $level)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(Training $training): array
    {
        $details = $training->details;
        $schedule = $details->schedule;
        $snapshot = $training->sopSnapshot;

        return [
            'work_team_id' => $training->teamId,
            'status' => $training->status,
            'name' => $details->name,
            'registration_url' => $details->registrationUrl?->value,
            'registration_start' => $schedule->registrationStart?->format('Y-m-d'),
            'registration_end' => $schedule->registrationEnd?->format('Y-m-d'),
            'training_start' => $schedule->trainingStart?->format('Y-m-d'),
            'training_end' => $schedule->trainingEnd?->format('Y-m-d'),
            'partner_location' => $details->partnerLocation,
            'participant_list_url' => $details->participantListUrl?->value,
            'method' => $details->method,
            'batch' => $details->batch,
            'syllabus_url' => $details->syllabusUrl?->value,
            'level' => $details->level,
            'certification' => $details->certification,
            'coc_shows_score' => $details->cocShowsScore,
            'execution' => $details->execution,
            'participant_category' => $details->participantCategory,
            'zonation' => $details->zonation,
            'lesson_hours' => $details->lessonHours,
            'participant_target' => $details->participantTarget,
            'registration_quota' => $details->registrationQuota,
            'learning_path_id' => $training->learningPathId,
            'sop_provision_id' => $snapshot?->provisionId,
            'sop_fiscal_year' => $snapshot?->fiscalYear,
            'sop_version' => $snapshot?->version,
            'sop_number' => $snapshot?->sopNumber,
            'sop_url' => $snapshot?->sopUrl,
            'sop_bap_days' => $snapshot?->deadlines->bapDays,
            'sop_sk_days' => $snapshot?->deadlines->skDays,
            'sop_st_days' => $snapshot?->deadlines->stDays,
            'sop_ready_days' => $snapshot?->deadlines->readyDays,
            'sop_is_fallback' => $snapshot?->isFallback,
            'finalized_at' => $training->finalizedAt,
        ];
    }

    private function toDomain(TrainingRecord $record): Training
    {
        $details = new TrainingDetails(
            name: $record->name,
            schedule: TrainingSchedule::of(
                $record->registration_start,
                $record->registration_end,
                $record->training_start,
                $record->training_end,
            ),
            registrationUrl: HttpUrl::fromNullable($record->registration_url, 'registration_url'),
            partnerLocation: $record->partner_location,
            participantListUrl: HttpUrl::fromNullable($record->participant_list_url, 'participant_list_url'),
            method: $record->method,
            batch: $record->batch,
            syllabusUrl: HttpUrl::fromNullable($record->syllabus_url, 'syllabus_url'),
            level: $record->level,
            certification: $record->certification,
            cocShowsScore: $record->coc_shows_score,
            execution: $record->execution,
            participantCategory: $record->participant_category,
            zonation: $record->zonation,
            lessonHours: $record->lesson_hours,
            participantTarget: $record->participant_target,
            registrationQuota: $record->registration_quota,
        );

        $snapshot = $record->sop_provision_id === null ? null : new SopSnapshot(
            provisionId: $record->sop_provision_id,
            fiscalYear: (int) $record->sop_fiscal_year,
            version: (int) $record->sop_version,
            sopNumber: $record->sop_number,
            sopUrl: $record->sop_url,
            deadlines: AdministrativeDeadlines::of((int) $record->sop_bap_days, (int) $record->sop_sk_days, (int) $record->sop_st_days, (int) $record->sop_ready_days),
            isFallback: (bool) $record->sop_is_fallback,
        );

        return Training::restore(
            id: $record->id,
            teamId: $record->work_team_id,
            status: $record->status,
            details: $details,
            learningPathId: $record->learning_path_id,
            sopSnapshot: $snapshot,
            finalizedAt: $record->finalized_at,
        );
    }

    private function levelTaken(): InvalidTraining
    {
        return new InvalidTraining('Level tersebut pada learning path ini sudah ditempati pelatihan lain.', 'level');
    }
}
