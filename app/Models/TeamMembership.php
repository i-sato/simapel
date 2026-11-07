<?php

namespace App\Models;

use App\Domain\Organization\TeamRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $work_team_id
 * @property int $user_id
 * @property TeamRole $role
 */
#[Fillable(['work_team_id', 'user_id', 'role'])]
class TeamMembership extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['role' => TeamRole::class];
    }

    /**
     * @return BelongsTo<WorkTeam, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(WorkTeam::class, 'work_team_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
