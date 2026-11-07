<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Persistensi template WA kustom. Aturan ada di App\Domain\Messaging.
 *
 * @property int $id
 * @property int $training_id
 * @property int $user_id
 * @property string $body
 */
#[Fillable(['training_id', 'user_id', 'body'])]
class MessageTemplateRecord extends Model
{
    protected $table = 'message_templates';
}
