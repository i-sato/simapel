<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property int|null $updated_by
 */
#[Fillable(['key', 'value', 'updated_by'])]
class Setting extends Model {}
