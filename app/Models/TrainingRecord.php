<?php

namespace App\Models;

use App\Domain\Shared\WorkRegion;
use App\Domain\Training\Certification;
use App\Domain\Training\ExecutionMode;
use App\Domain\Training\ParticipantCategory;
use App\Domain\Training\TrainingLevel;
use App\Domain\Training\TrainingMethod;
use App\Domain\Training\TrainingStatus;
use App\Domain\Training\Zonation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persistensi pelatihan. Aturan ada di App\Domain\Training\Training.
 *
 * @property int $id
 * @property int $work_team_id
 * @property TrainingStatus $status
 * @property string $name
 * @property string|null $registration_url
 * @property CarbonImmutable|null $registration_start
 * @property CarbonImmutable|null $registration_end
 * @property CarbonImmutable|null $training_start
 * @property CarbonImmutable|null $training_end
 * @property string|null $partner_location
 * @property string|null $participant_list_url
 * @property TrainingMethod|null $method
 * @property int|null $batch
 * @property string|null $syllabus_url
 * @property TrainingLevel|null $level
 * @property Certification|null $certification
 * @property bool|null $coc_shows_score
 * @property ExecutionMode|null $execution
 * @property ParticipantCategory|null $participant_category
 * @property Zonation|null $zonation
 * @property int|null $lesson_hours
 * @property int|null $participant_target
 * @property int|null $registration_quota
 * @property int|null $learning_path_id
 * @property WorkRegion|null $region
 * @property string $registration_closes_at
 * @property int|null $partner_id
 * @property-read PartnerRecord|null $partner
 * @property int|null $sop_provision_id
 * @property int|null $sop_fiscal_year
 * @property int|null $sop_version
 * @property string|null $sop_number
 * @property string|null $sop_url
 * @property int|null $sop_bap_days
 * @property int|null $sop_sk_days
 * @property int|null $sop_st_days
 * @property int|null $sop_ready_days
 * @property bool|null $sop_is_fallback
 * @property CarbonImmutable|null $finalized_at
 * @property-read WorkTeam $team
 * @property-read LearningPathRecord|null $learningPath
 * @property-read Collection<int, TrainingAssignmentRecord> $assignments
 */
#[Fillable([
    'work_team_id', 'status', 'name', 'registration_url', 'registration_start', 'registration_end',
    'training_start', 'training_end', 'partner_location', 'participant_list_url', 'method', 'batch',
    'syllabus_url', 'level', 'certification', 'coc_shows_score', 'execution', 'participant_category',
    'zonation', 'region', 'registration_closes_at', 'partner_id', 'lesson_hours', 'participant_target', 'registration_quota', 'learning_path_id',
    'sop_provision_id', 'sop_fiscal_year', 'sop_version', 'sop_number', 'sop_url', 'sop_bap_days',
    'sop_sk_days', 'sop_st_days', 'sop_ready_days', 'sop_is_fallback', 'finalized_at', 'finalized_by',
    'created_by', 'updated_by',
])]
class TrainingRecord extends Model
{
    protected $table = 'trainings';

    /** @var array<string, mixed> */
    protected $attributes = ['registration_closes_at' => '23:59:00'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TrainingStatus::class,
            'registration_start' => 'immutable_date',
            'registration_end' => 'immutable_date',
            'training_start' => 'immutable_date',
            'training_end' => 'immutable_date',
            'method' => TrainingMethod::class,
            'batch' => 'integer',
            'level' => TrainingLevel::class,
            'certification' => Certification::class,
            'coc_shows_score' => 'boolean',
            'execution' => ExecutionMode::class,
            'participant_category' => ParticipantCategory::class,
            'zonation' => Zonation::class,
            'region' => WorkRegion::class,
            'lesson_hours' => 'integer',
            'participant_target' => 'integer',
            'registration_quota' => 'integer',
            'sop_fiscal_year' => 'integer',
            'sop_version' => 'integer',
            'sop_bap_days' => 'integer',
            'sop_sk_days' => 'integer',
            'sop_st_days' => 'integer',
            'sop_ready_days' => 'integer',
            'sop_is_fallback' => 'boolean',
            'finalized_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkTeam, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(WorkTeam::class, 'work_team_id');
    }

    /**
     * @return BelongsTo<PartnerRecord, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(PartnerRecord::class, 'partner_id');
    }

    /**
     * @return HasMany<TrainingAssignmentRecord, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignmentRecord::class, 'training_id');
    }

    /**
     * @return BelongsTo<LearningPathRecord, $this>
     */
    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPathRecord::class, 'learning_path_id');
    }
}
