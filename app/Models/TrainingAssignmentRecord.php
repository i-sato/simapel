<?php

namespace App\Models;

use App\Domain\Assignment\AssignmentRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persistensi penugasan Admin/Panitia. Aturan ada di App\Domain\Assignment.
 *
 * @property int $id
 * @property int $training_id
 * @property int $user_id
 * @property AssignmentRole $role
 * @property string|null $force_reason
 * @property-read TrainingRecord $training
 * @property-read User $user
 */
#[Fillable(['training_id', 'user_id', 'role', 'force_reason', 'created_by'])]
class TrainingAssignmentRecord extends Model
{
    protected $table = 'training_assignments';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['role' => AssignmentRole::class];
    }

    /**
     * @return BelongsTo<TrainingRecord, $this>
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(TrainingRecord::class, 'training_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
