<?php

namespace Tests\Unit\Application\Sop;

use App\Application\Sop\ActivateSopProvision;
use App\Application\Sop\CopySopProvisionFromPreviousYear;
use App\Application\Sop\CreateSopProvisionDraft;
use App\Application\Sop\DeleteSopProvisionDraft;
use App\Application\Sop\Exceptions\SopProvisionNotFound;
use App\Application\Sop\ResolveSopProvision;
use App\Application\Sop\SopProvisionInput;
use App\Application\Sop\UpdateSopProvisionDraft;
use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Shared\BusinessRuleViolation;
use App\Domain\Sop\InvalidSopProvision;
use App\Domain\Sop\SopProvisionLocked;
use App\Domain\Sop\SopProvisionStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\FixedClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemorySopProvisionRepository;
use Tests\Support\SopProvisionBuilder;

final class SopProvisionUseCasesTest extends TestCase
{
    private InMemorySopProvisionRepository $provisions;

    private InMemoryAuditTrail $audit;

    private ImmediateTransaction $transaction;

    protected function setUp(): void
    {
        $this->provisions = new InMemorySopProvisionRepository;
        $this->audit = new InMemoryAuditTrail;
        $this->transaction = new ImmediateTransaction;
    }

    private function input(string $from = '2026-01-01', ?string $url = 'https://drive.example.test/sop', int $ready = 3, ?string $sopDate = '2026-01-01'): SopProvisionInput
    {
        return new SopProvisionInput('SOP/01/2026', $sopDate, $url, $from, 1, 2, 2, $ready, 'Catatan uji');
    }

    private function create(WorkTeamCode $team = WorkTeamCode::Dta, int $year = 2026, ?SopProvisionInput $input = null): int
    {
        return (new CreateSopProvisionDraft($this->provisions, $this->audit, $this->transaction))
            ->handle($team, $year, $input ?? $this->input(), actorId: 1);
    }

    private function activate(int $id): void
    {
        (new ActivateSopProvision($this->provisions, $this->audit, $this->transaction))->handle($id, actorId: 1);
    }

    public function test_creates_numbered_drafts_with_lock_and_audit(): void
    {
        $first = $this->create();
        $second = $this->create(input: $this->input('2026-06-01'));

        $this->assertSame(1, $this->provisions->provisions[$first]->version);
        $this->assertSame(2, $this->provisions->provisions[$second]->version);
        $this->assertSame(SopProvisionStatus::Draf, $this->provisions->provisions[$second]->status);
        $this->assertSame(2, $this->provisions->locks);
        $this->assertSame(AuditEventType::SopProvisionDrafted, $this->audit->events[0]->type);
        $this->assertSame(3, $this->audit->events[0]->context['ready_days']);
    }

    public function test_rejects_invalid_input_before_touching_storage(): void
    {
        foreach ([$this->input(ready: 1), $this->input(url: 'javascript:alert(1)'), $this->input(sopDate: '01-01-2026'), $this->input(from: '')] as $invalid) {
            try {
                $this->create(input: $invalid);
                $this->fail('Input tidak valid seharusnya ditolak.');
            } catch (BusinessRuleViolation) {
                // dilanjutkan ke input berikutnya
            }
        }

        $this->assertSame([], $this->provisions->provisions);
        $this->assertSame([], $this->audit->events);
    }

    public function test_updates_draft_and_audits_old_and_new_values(): void
    {
        $id = $this->create();

        (new UpdateSopProvisionDraft($this->provisions, $this->audit, $this->transaction))
            ->handle($id, $this->input(ready: 5), actorId: 1);

        $this->assertSame(5, $this->provisions->provisions[$id]->deadlines->readyDays);
        $event = $this->audit->events[1];
        $this->assertSame(AuditEventType::SopProvisionUpdated, $event->type);
        $this->assertSame(3, $event->context['old_ready_days']);
        $this->assertSame(5, $event->context['new_ready_days']);
    }

    public function test_referenced_provision_cannot_be_updated_or_deleted(): void
    {
        $id = $this->create();
        $this->provisions->referenced = [$id];

        try {
            (new UpdateSopProvisionDraft($this->provisions, $this->audit, $this->transaction))->handle($id, $this->input(ready: 5), 1);
            $this->fail('Ketentuan yang dirujuk seharusnya terkunci.');
        } catch (SopProvisionLocked) {
        }

        $this->expectException(SopProvisionLocked::class);
        (new DeleteSopProvisionDraft($this->provisions, $this->audit, $this->transaction))->handle($id, 1);
    }

    public function test_active_provision_cannot_be_updated_or_deleted(): void
    {
        $id = $this->create();
        $this->activate($id);

        try {
            (new UpdateSopProvisionDraft($this->provisions, $this->audit, $this->transaction))->handle($id, $this->input(ready: 5), 1);
            $this->fail('Ketentuan Berlaku seharusnya terkunci.');
        } catch (SopProvisionLocked) {
        }

        $this->expectException(SopProvisionLocked::class);
        (new DeleteSopProvisionDraft($this->provisions, $this->audit, $this->transaction))->handle($id, 1);
    }

    public function test_deletes_draft_with_audit(): void
    {
        $id = $this->create();

        (new DeleteSopProvisionDraft($this->provisions, $this->audit, $this->transaction))->handle($id, 1);

        $this->assertSame([], $this->provisions->provisions);
        $this->assertSame(AuditEventType::SopProvisionDeleted, $this->audit->events[1]->type);
    }

    public function test_activation_supersedes_previous_version_of_same_year_only(): void
    {
        $previousYear = $this->create(year: 2025, input: $this->input('2025-01-01', sopDate: '2025-01-01'));
        $this->activate($previousYear);
        $v1 = $this->create();
        $this->activate($v1);
        $otherTeam = $this->create(team: WorkTeamCode::Kdd);
        $this->activate($otherTeam);
        $v2 = $this->create(input: $this->input('2026-07-01'));

        $this->activate($v2);

        $this->assertSame(SopProvisionStatus::Berlaku, $this->provisions->provisions[$v2]->status);
        $this->assertSame(SopProvisionStatus::Diganti, $this->provisions->provisions[$v1]->status);
        $this->assertSame('2026-07-01', $this->provisions->provisions[$v1]->supersededFrom?->format('Y-m-d'));
        $this->assertSame(SopProvisionStatus::Berlaku, $this->provisions->provisions[$previousYear]->status);
        $this->assertSame(SopProvisionStatus::Berlaku, $this->provisions->provisions[$otherTeam]->status);
        $types = array_map(fn ($event) => $event->type, array_slice($this->audit->events, -2));
        $this->assertSame([AuditEventType::SopProvisionActivated, AuditEventType::SopProvisionSuperseded], $types);
    }

    public function test_activation_of_incomplete_draft_is_rejected(): void
    {
        $id = $this->create(input: $this->input(url: null));

        $this->expectException(InvalidSopProvision::class);
        $this->activate($id);
    }

    public function test_unknown_provision_is_not_found(): void
    {
        $this->expectException(SopProvisionNotFound::class);
        $this->activate(99);
    }

    public function test_copies_version_in_force_at_year_end_as_new_draft(): void
    {
        $this->provisions->provisions[1] = SopProvisionBuilder::active(year: 2026, from: '2026-01-01', version: 1, id: 1)->supersede(new DateTimeImmutable('2026-07-01'));
        $this->provisions->provisions[2] = SopProvisionBuilder::active(year: 2026, from: '2026-07-01', version: 2, id: 2);
        $this->provisions->provisions[3] = SopProvisionBuilder::draft(year: 2026, from: '2026-11-01', version: 3, id: 3);

        $id = (new CopySopProvisionFromPreviousYear($this->provisions, $this->audit, $this->transaction))
            ->handle(WorkTeamCode::Dta, 2027, actorId: 1);

        $copy = $this->provisions->provisions[$id];
        $this->assertSame(2027, $copy->fiscalYear);
        $this->assertSame(1, $copy->version);
        $this->assertSame(SopProvisionStatus::Draf, $copy->status);
        $this->assertSame(2, $this->audit->events[0]->context['copied_from_id']);
    }

    public function test_copy_without_previous_provision_fails(): void
    {
        $this->expectException(SopProvisionNotFound::class);
        (new CopySopProvisionFromPreviousYear($this->provisions, $this->audit, $this->transaction))
            ->handle(WorkTeamCode::Asn, 2027, actorId: 1);
    }

    public function test_resolve_uses_clock_by_default(): void
    {
        $this->provisions->provisions[1] = SopProvisionBuilder::active(year: 2026, from: '2026-01-01', id: 1);
        $resolve = new ResolveSopProvision($this->provisions, new FixedClock(new DateTimeImmutable('2027-01-02')));

        $this->assertTrue($resolve->handle(WorkTeamCode::Dta)?->isFallback);
        $this->assertFalse($resolve->handle(WorkTeamCode::Dta, new DateTimeImmutable('2026-12-31'))?->isFallback);
        $this->assertNull($resolve->handle(WorkTeamCode::Kdd));
    }
}
