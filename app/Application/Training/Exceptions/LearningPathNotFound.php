<?php

declare(strict_types=1);

namespace App\Application\Training\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class LearningPathNotFound extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Learning path tidak ditemukan.', 'learning_path_id');
    }
}
