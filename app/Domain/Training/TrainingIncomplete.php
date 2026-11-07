<?php

declare(strict_types=1);

namespace App\Domain\Training;

use App\Domain\Shared\BusinessRuleViolation;

/**
 * Data pelatihan belum lengkap untuk difinalisasi.
 */
final class TrainingIncomplete extends BusinessRuleViolation
{
    /**
     * @param  array<string, string>  $missing  field => label
     */
    public function __construct(public readonly array $missing)
    {
        parent::__construct('Lengkapi sebelum finalisasi: '.implode(', ', $missing).'.', 'general');
    }
}
