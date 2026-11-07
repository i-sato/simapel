<?php

declare(strict_types=1);

namespace App\Application\Sop;

use App\Domain\Shared\HttpUrl;
use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Sop\InvalidSopProvision;
use App\Domain\Sop\SopReference;
use DateTimeImmutable;

/**
 * Mengubah input formulir menjadi value object Domain.
 */
final class SopProvisionFactory
{
    public static function reference(SopProvisionInput $input): SopReference
    {
        return SopReference::of(
            $input->sopNumber,
            self::date($input->sopEffectiveDate, 'sop_effective_date'),
            HttpUrl::fromNullable($input->sopUrl, 'sop_url'),
        );
    }

    public static function deadlines(SopProvisionInput $input): AdministrativeDeadlines
    {
        return AdministrativeDeadlines::of($input->bapDays, $input->skDays, $input->stDays, $input->readyDays);
    }

    public static function effectiveFrom(SopProvisionInput $input): DateTimeImmutable
    {
        return self::date($input->effectiveFrom, 'effective_from')
            ?? throw new InvalidSopProvision('Tanggal mulai berlaku wajib diisi.', 'effective_from');
    }

    private static function date(?string $value, string $field): ?DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        if ($date === false || $date->format('Y-m-d') !== trim($value)) {
            throw new InvalidSopProvision('Format tanggal harus YYYY-MM-DD.', $field);
        }

        return $date;
    }
}
