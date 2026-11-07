<?php

declare(strict_types=1);

namespace App\Domain\Sop;

use App\Domain\Shared\BusinessRuleViolation;

final class SopProvisionLocked extends BusinessRuleViolation
{
    public function __construct(string $message = 'Hanya ketentuan berstatus Draf yang dapat diubah atau dihapus. Buat versi baru untuk revisi.')
    {
        parent::__construct($message);
    }
}
