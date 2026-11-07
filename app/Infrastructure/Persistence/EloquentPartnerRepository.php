<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Partner\Contracts\PartnerRepository;
use App\Domain\Partner\InvalidPartner;
use App\Domain\Partner\Partner;
use App\Models\PartnerRecord;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentPartnerRepository implements PartnerRepository
{
    public function find(int $id): ?Partner
    {
        $record = PartnerRecord::query()->find($id);

        return $record === null ? null : new Partner($record->id, $record->name, $record->region);
    }

    public function findByNormalizedName(string $normalized): ?Partner
    {
        $record = PartnerRecord::query()->where('normalized_name', $normalized)->first();

        return $record === null ? null : new Partner($record->id, $record->name, $record->region);
    }

    public function insert(Partner $partner, int $actorId): int
    {
        try {
            $record = PartnerRecord::query()->create([
                'name' => $partner->name,
                'normalized_name' => $partner->normalizedName(),
                'region' => $partner->region,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new InvalidPartner('Mitra dengan nama serupa sudah terdaftar.', 'partner_name');
        }

        return (int) $record->getKey();
    }

    public function update(Partner $partner, int $actorId): void
    {
        try {
            PartnerRecord::query()->findOrFail($partner->id)->forceFill([
                'name' => $partner->name,
                'normalized_name' => $partner->normalizedName(),
                'region' => $partner->region,
                'updated_by' => $actorId,
            ])->save();
        } catch (UniqueConstraintViolationException) {
            throw new InvalidPartner('Mitra dengan nama serupa sudah terdaftar.', 'partner_name');
        }
    }
}
