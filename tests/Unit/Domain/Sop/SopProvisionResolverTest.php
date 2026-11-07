<?php

namespace Tests\Unit\Domain\Sop;

use App\Domain\Sop\SopProvision;
use App\Domain\Sop\SopProvisionResolver;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\SopProvisionBuilder;

final class SopProvisionResolverTest extends TestCase
{
    /**
     * @return list<SopProvision>
     */
    private function history(): array
    {
        // 2025 v1 berlaku sepanjang tahun; 2026 v1 berlaku mulai 1 Jan lalu diganti v2 mulai 1 Jul.
        $v2025 = SopProvisionBuilder::active(year: 2025, from: '2025-01-01', id: 1);
        $v1 = SopProvisionBuilder::active(year: 2026, from: '2026-01-01', version: 1, id: 2)->supersede(new DateTimeImmutable('2026-07-01'));
        $v2 = SopProvisionBuilder::active(year: 2026, from: '2026-07-01', version: 2, id: 3);
        $draft = SopProvisionBuilder::draft(year: 2026, from: '2026-10-01', version: 3, id: 4);

        return [$v2025, $v1, $v2, $draft];
    }

    /**
     * @return iterable<string, array{string, int, bool}>
     */
    public static function dates(): iterable
    {
        yield 'awal tahun' => ['2026-01-01', 2, false];
        yield 'sebelum revisi' => ['2026-06-30 23:59:00', 2, false];
        yield 'hari revisi berlaku' => ['2026-07-01', 3, false];
        yield 'akhir tahun (draf diabaikan)' => ['2026-12-31', 3, false];
        yield 'tahun lalu' => ['2025-05-05', 1, false];
        yield 'tahun baru tanpa ketentuan' => ['2027-01-15', 3, true];
    }

    #[DataProvider('dates')]
    public function test_resolves_provision_in_force(string $on, int $expectedId, bool $fallback): void
    {
        $resolved = SopProvisionResolver::resolve($this->history(), new DateTimeImmutable($on));

        $this->assertNotNull($resolved);
        $this->assertSame($expectedId, $resolved->provision->id);
        $this->assertSame($fallback, $resolved->isFallback);
    }

    public function test_date_before_first_version_of_year_falls_back_to_previous_year(): void
    {
        $provisions = [
            SopProvisionBuilder::active(year: 2026, from: '2026-01-01', id: 1),
            SopProvisionBuilder::active(year: 2027, from: '2027-02-01', id: 2),
        ];

        $resolved = SopProvisionResolver::resolve($provisions, new DateTimeImmutable('2027-01-20'));

        $this->assertNotNull($resolved);
        $this->assertSame(1, $resolved->provision->id);
        $this->assertTrue($resolved->isFallback);
    }

    public function test_returns_null_without_any_provision_in_force(): void
    {
        $this->assertNull(SopProvisionResolver::resolve([], new DateTimeImmutable('2026-05-01')));
        $this->assertNull(SopProvisionResolver::resolve([SopProvisionBuilder::draft(id: 1)], new DateTimeImmutable('2026-05-01')));
        $this->assertNull(SopProvisionResolver::resolve([SopProvisionBuilder::active(year: 2027, from: '2027-01-01', id: 1)], new DateTimeImmutable('2026-05-01')));
    }
}
