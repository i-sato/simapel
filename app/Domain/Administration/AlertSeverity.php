<?php

declare(strict_types=1);

namespace App\Domain\Administration;

enum AlertSeverity: string
{
    case Notice = 'notice';
    case Warning = 'warning';
    case Critical = 'critical';
}
