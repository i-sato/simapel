<?php

namespace Tests\Support;

use App\Domain\Organization\WorkTeamCode;
use App\Domain\Shared\HttpUrl;
use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Sop\SopProvision;
use App\Domain\Sop\SopReference;
use DateTimeImmutable;

/**
 * Pembuat ketentuan SOP sintetis untuk unit test.
 */
final class SopProvisionBuilder
{
    public static function draft(
        int $year = 2026,
        string $from = '2026-01-01',
        int $version = 1,
        WorkTeamCode $team = WorkTeamCode::Dta,
        bool $complete = true,
        int $id = 0,
    ): SopProvision {
        $provision = SopProvision::draft(
            $team,
            $year,
            $version,
            SopReference::of(
                $complete ? 'SOP/'.$year.'/'.$version : null,
                new DateTimeImmutable($year.'-01-01'),
                $complete ? HttpUrl::fromString('https://drive.example.test/sop-'.$year.'-'.$version) : null,
            ),
            new DateTimeImmutable($from),
            AdministrativeDeadlines::of(1, 2, 2, 3),
        );

        return $id > 0 ? $provision->withId($id) : $provision;
    }

    public static function active(int $year = 2026, string $from = '2026-01-01', int $version = 1, WorkTeamCode $team = WorkTeamCode::Dta, int $id = 0): SopProvision
    {
        return self::draft($year, $from, $version, $team, true, $id)->activate();
    }
}
