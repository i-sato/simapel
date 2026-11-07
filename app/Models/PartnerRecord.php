<?php

namespace App\Models;

use App\Domain\Shared\WorkRegion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persistensi mitra. Aturan ada di App\Domain\Partner.
 *
 * @property int $id
 * @property string $name
 * @property string $normalized_name
 * @property WorkRegion $region
 */
#[Fillable(['name', 'normalized_name', 'region', 'created_by', 'updated_by'])]
class PartnerRecord extends Model
{
    protected $table = 'partners';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['region' => WorkRegion::class];
    }

    /**
     * @return HasMany<TrainingRecord, $this>
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(TrainingRecord::class, 'partner_id');
    }
}
