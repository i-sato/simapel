<?php

declare(strict_types=1);

namespace App\Domain\Settings;

use App\Domain\Shared\BusinessRuleViolation;

final class InvalidSetting extends BusinessRuleViolation
{
    public function __construct(GlobalSetting $setting, string $message)
    {
        parent::__construct($message, $setting->value);
    }
}
