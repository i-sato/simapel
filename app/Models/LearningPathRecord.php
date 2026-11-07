<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persistensi learning path. Aturan ada di App\Domain\Training\LearningPath.
 *
 * @property int $id
 * @property int $work_team_id
 * @property string $name
 * @property-read WorkTeam $team
 */
#[Fillable(['work_team_id', 'name', 'created_by'])]
class LearningPathRecord extends Model
{
    protected $table = 'learning_paths';

    /**
     * @return BelongsTo<WorkTeam, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(WorkTeam::class, 'work_team_id');
    }

    /**
     * @return HasMany<TrainingRecord, $this>
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(TrainingRecord::class, 'learning_path_id');
    }
}
