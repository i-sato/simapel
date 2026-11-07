<?php

declare(strict_types=1);

namespace App\Domain\Shared;

final class InvalidDateRange extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Tanggal akhir tidak boleh sebelum tanggal awal.');
    }
}
