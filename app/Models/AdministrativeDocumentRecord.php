<?php

namespace App\Models;

use App\Domain\Administration\DocumentStatus;
use App\Domain\Administration\DocumentType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persistensi dokumen administrasi. Aturan ada di App\Domain\Administration.
 *
 * @property int $id
 * @property int $training_id
 * @property DocumentType $type
 * @property DocumentStatus $status
 * @property string|null $number
 * @property CarbonImmutable|null $document_date
 * @property string|null $file_url
 * @property int|null $assignee_id
 * @property-read User|null $assignee
 */
#[Fillable(['training_id', 'type', 'status', 'number', 'document_date', 'file_url', 'assignee_id', 'updated_by'])]
class AdministrativeDocumentRecord extends Model
{
    protected $table = 'administrative_documents';

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'belum'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'document_date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
