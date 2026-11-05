<?php

namespace App\Models;

use App\Domain\Audit\AuditEventType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Jejak audit bersifat append-only: baris yang tersimpan tidak boleh diubah
 * atau dihapus melalui aplikasi.
 *
 * @property int $id
 * @property CarbonImmutable $occurred_at
 * @property AuditEventType $event_type
 * @property int|null $actor_user_id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string $description
 * @property array<string, scalar|null>|null $context
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $channel
 */
#[Fillable([
    'occurred_at', 'event_type', 'actor_user_id', 'subject_type', 'subject_id',
    'description', 'context', 'ip_address', 'user_agent', 'channel',
])]
class AuditLog extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'event_type' => AuditEventType::class,
            'context' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Audit log tidak dapat diubah.'));
        static::deleting(fn (): never => throw new LogicException('Audit log tidak dapat dihapus.'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
