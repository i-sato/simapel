<?php

namespace App\Models;

use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\SopProvisionStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Persistensi ketentuan SOP. Aturan ada di App\Domain\Sop\SopProvision.
 *
 * @property int $id
 * @property WorkTeamCode $team_code
 * @property int $fiscal_year
 * @property int $version
 * @property SopProvisionStatus $status
 * @property string|null $sop_number
 * @property CarbonImmutable|null $sop_effective_date
 * @property string|null $sop_url
 * @property CarbonImmutable $effective_from
 * @property CarbonImmutable|null $superseded_from
 * @property int $bap_days
 * @property int $sk_days
 * @property int $st_days
 * @property int $ready_days
 * @property string|null $note
 */
#[Fillable([
    'team_code', 'fiscal_year', 'version', 'status', 'sop_number', 'sop_effective_date', 'sop_url',
    'effective_from', 'superseded_from', 'bap_days', 'sk_days', 'st_days', 'ready_days', 'note',
    'created_by', 'updated_by',
])]
class SopProvisionRecord extends Model
{
    protected $table = 'sop_provisions';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'team_code' => WorkTeamCode::class,
            'status' => SopProvisionStatus::class,
            'fiscal_year' => 'integer',
            'version' => 'integer',
            'sop_effective_date' => 'immutable_date',
            'effective_from' => 'immutable_date',
            'superseded_from' => 'immutable_date',
            'bap_days' => 'integer',
            'sk_days' => 'integer',
            'st_days' => 'integer',
            'ready_days' => 'integer',
        ];
    }
}
