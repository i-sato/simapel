<?php

declare(strict_types=1);

namespace App\Application\Sop;

use App\Domain\Sop\SopProvision;

/**
 * Representasi skalar ketentuan untuk konteks audit (bukan data pribadi).
 */
final class SopProvisionAudit
{
    /**
     * @return array<string, scalar|null>
     */
    public static function snapshot(SopProvision $provision, string $prefix = ''): array
    {
        return [
            $prefix.'team' => $provision->team->value,
            $prefix.'fiscal_year' => $provision->fiscalYear,
            $prefix.'version' => $provision->version,
            $prefix.'status' => $provision->status->value,
            $prefix.'sop_number' => $provision->sop->number,
            $prefix.'sop_effective_date' => $provision->sop->effectiveDate?->format('Y-m-d'),
            $prefix.'sop_url' => $provision->sop->url?->value,
            $prefix.'effective_from' => $provision->effectiveFrom->format('Y-m-d'),
            $prefix.'superseded_from' => $provision->supersededFrom?->format('Y-m-d'),
            $prefix.'bap_days' => $provision->deadlines->bapDays,
            $prefix.'sk_days' => $provision->deadlines->skDays,
            $prefix.'st_days' => $provision->deadlines->stDays,
            $prefix.'ready_days' => $provision->deadlines->readyDays,
        ];
    }

    public static function label(SopProvision $provision): string
    {
        return sprintf('%s %d versi %d', $provision->team->value, $provision->fiscalYear, $provision->version);
    }
}
