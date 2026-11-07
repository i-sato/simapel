<?php

namespace App\Models;

use App\Domain\Organization\WorkTeamCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property WorkTeamCode $code
 * @property string $name
 * @property int $year
 * @property string|null $registration_guide_url
 */
#[Fillable(['code', 'name', 'year', 'registration_guide_url'])]
class WorkTeam extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => WorkTeamCode::class,
            'year' => 'integer',
        ];
    }

    /**
     * @return HasMany<TeamMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function label(): string
    {
        return sprintf('%s (%s)', $this->name, $this->code->value);
    }
}
