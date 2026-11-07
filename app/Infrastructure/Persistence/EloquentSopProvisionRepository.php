<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Sop\Contracts\SopProvisionRepository;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Shared\HttpUrl;
use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Sop\SopProvision;
use App\Domain\Sop\SopReference;
use App\Models\SopProvisionRecord;
use App\Models\TrainingRecord;
use DateTimeImmutable;

final class EloquentSopProvisionRepository implements SopProvisionRepository
{
    public function find(int $id): ?SopProvision
    {
        $record = SopProvisionRecord::query()->find($id);

        return $record === null ? null : $this->toDomain($record);
    }

    public function forTeam(WorkTeamCode $team, bool $lock = false): array
    {
        $records = SopProvisionRecord::query()
            ->where('team_code', $team)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->orderBy('fiscal_year')
            ->orderBy('version')
            ->get();

        return array_values($records->map(fn (SopProvisionRecord $record): SopProvision => $this->toDomain($record))->all());
    }

    public function nextVersion(WorkTeamCode $team, int $fiscalYear): int
    {
        return (int) SopProvisionRecord::query()->where('team_code', $team)->where('fiscal_year', $fiscalYear)->max('version') + 1;
    }

    public function insert(SopProvision $provision, int $actorId): int
    {
        $record = SopProvisionRecord::query()->create($this->attributes($provision) + ['created_by' => $actorId, 'updated_by' => $actorId]);

        return (int) $record->getKey();
    }

    public function save(SopProvision $provision, int $actorId): void
    {
        SopProvisionRecord::query()->findOrFail($provision->id)
            ->forceFill($this->attributes($provision) + ['updated_by' => $actorId])
            ->save();
    }

    public function delete(int $id): void
    {
        SopProvisionRecord::query()->whereKey($id)->delete();
    }

    public function isReferenced(int $id): bool
    {
        return TrainingRecord::query()->where('sop_provision_id', $id)->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(SopProvision $provision): array
    {
        return [
            'team_code' => $provision->team,
            'fiscal_year' => $provision->fiscalYear,
            'version' => $provision->version,
            'status' => $provision->status,
            'sop_number' => $provision->sop->number,
            'sop_effective_date' => $provision->sop->effectiveDate?->format('Y-m-d'),
            'sop_url' => $provision->sop->url?->value,
            'effective_from' => $provision->effectiveFrom->format('Y-m-d'),
            'superseded_from' => $provision->supersededFrom?->format('Y-m-d'),
            'bap_days' => $provision->deadlines->bapDays,
            'sk_days' => $provision->deadlines->skDays,
            'st_days' => $provision->deadlines->stDays,
            'ready_days' => $provision->deadlines->readyDays,
            'note' => $provision->note,
        ];
    }

    private function toDomain(SopProvisionRecord $record): SopProvision
    {
        return SopProvision::restore(
            id: $record->id,
            team: $record->team_code,
            fiscalYear: $record->fiscal_year,
            version: $record->version,
            status: $record->status,
            sop: SopReference::of(
                $record->sop_number,
                $record->sop_effective_date === null ? null : new DateTimeImmutable($record->sop_effective_date->format('Y-m-d')),
                HttpUrl::fromNullable($record->sop_url),
            ),
            effectiveFrom: new DateTimeImmutable($record->effective_from->format('Y-m-d')),
            supersededFrom: $record->superseded_from === null ? null : new DateTimeImmutable($record->superseded_from->format('Y-m-d')),
            deadlines: AdministrativeDeadlines::of($record->bap_days, $record->sk_days, $record->st_days, $record->ready_days),
            note: $record->note,
        );
    }
}
