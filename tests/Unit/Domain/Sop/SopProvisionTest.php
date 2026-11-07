<?php

namespace Tests\Unit\Domain\Sop;

use App\Domain\Sop\InvalidSopProvision;
use App\Domain\Sop\SopProvisionHistory;
use App\Domain\Sop\SopProvisionLocked;
use App\Domain\Sop\SopProvisionStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\SopProvisionBuilder;

final class SopProvisionTest extends TestCase
{
    public function test_effective_date_must_fall_within_fiscal_year(): void
    {
        try {
            SopProvisionBuilder::draft(year: 2026, from: '2027-01-01');
            $this->fail('Tanggal di luar tahun anggaran seharusnya ditolak.');
        } catch (InvalidSopProvision $exception) {
            $this->assertSame('effective_from', $exception->field());
        }
    }

    public function test_activation_requires_sop_number_and_link(): void
    {
        $this->expectException(InvalidSopProvision::class);
        SopProvisionBuilder::draft(complete: false)->activate();
    }

    public function test_draft_becomes_active_and_then_locked(): void
    {
        $active = SopProvisionBuilder::active();

        $this->assertSame(SopProvisionStatus::Berlaku, $active->status);

        $this->expectException(SopProvisionLocked::class);
        $active->revise($active->sop, $active->effectiveFrom, $active->deadlines, null);
    }

    public function test_draft_can_be_revised_but_not_activated_twice(): void
    {
        $draft = SopProvisionBuilder::draft();
        $revised = $draft->revise($draft->sop, new DateTimeImmutable('2026-03-01'), $draft->deadlines, '  catatan  ');

        $this->assertSame('2026-03-01', $revised->effectiveFrom->format('Y-m-d'));
        $this->assertSame('catatan', $revised->note);

        $this->expectException(SopProvisionLocked::class);
        $revised->activate()->activate();
    }

    public function test_copy_for_next_year_creates_january_first_draft(): void
    {
        $copy = SopProvisionBuilder::active(version: 2)->copyForYear(2027, 1);

        $this->assertSame(SopProvisionStatus::Draf, $copy->status);
        $this->assertSame(2027, $copy->fiscalYear);
        $this->assertSame('2027-01-01', $copy->effectiveFrom->format('Y-m-d'));
        $this->assertSame(3, $copy->deadlines->readyDays);
        $this->assertStringContainsString('versi 2 tahun 2026', (string) $copy->note);
    }

    public function test_activating_mid_year_revision_supersedes_previous_version(): void
    {
        $current = SopProvisionBuilder::active(from: '2026-01-01', version: 1, id: 1);
        $otherYear = SopProvisionBuilder::active(year: 2025, from: '2025-01-01', id: 3);
        $revision = SopProvisionBuilder::draft(from: '2026-07-01', version: 2, id: 2);

        $changed = SopProvisionHistory::activate($revision, [$current, $revision, $otherYear]);

        $this->assertCount(2, $changed);
        $this->assertSame(SopProvisionStatus::Berlaku, $changed[0]->status);
        $this->assertSame(SopProvisionStatus::Diganti, $changed[1]->status);
        $this->assertSame('2026-07-01', $changed[1]->supersededFrom?->format('Y-m-d'));
    }

    public function test_revision_must_start_after_the_version_in_force(): void
    {
        $current = SopProvisionBuilder::active(from: '2026-07-01', id: 1);
        $revision = SopProvisionBuilder::draft(from: '2026-07-01', version: 2, id: 2);

        $this->expectException(InvalidSopProvision::class);
        SopProvisionHistory::activate($revision, [$current, $revision]);
    }
}
