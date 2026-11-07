<?php

namespace Tests\Unit\Application\Administration;

use App\Application\Administration\DashboardOverview;
use App\Application\Administration\DelegateDocument;
use App\Application\Administration\DocumentAccess;
use App\Application\Administration\DocumentInput;
use App\Application\Administration\TrainingAlertsQuery;
use App\Application\Administration\UpdateDocument;
use App\Application\Assignment\TrainingWorkload;
use App\Application\Assignment\UpdateTrainingAssignments;
use App\Application\Settings\GlobalSettings;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\Exceptions\TrainingNotFound;
use App\Application\Training\TrainingActorProvider;
use App\Domain\Administration\AlertKind;
use App\Domain\Administration\DocumentStatus;
use App\Domain\Administration\DocumentType;
use App\Domain\Administration\InvalidDocument;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Role;
use App\Domain\Organization\TeamRole;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Shared\BusinessRuleViolation;
use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Training\SopSnapshot;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\FixedClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAdministrativeDocumentRepository;
use Tests\Support\Fakes\InMemoryAssignmentCandidateRepository;
use Tests\Support\Fakes\InMemoryAssignmentRepository;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemorySettingsRepository;
use Tests\Support\Fakes\InMemoryTrainingRepository;
use Tests\Support\Fakes\InMemoryUserAccountRepository;
use Tests\Support\Fakes\InMemoryWorkTeamRepository;
use Tests\Support\TrainingBuilder;

final class AdministrationUseCasesTest extends TestCase
{
    private InMemoryTrainingRepository $trainings;

    private InMemoryAdministrativeDocumentRepository $documents;

    private InMemoryAssignmentRepository $assignments;

    private InMemoryAssignmentCandidateRepository $candidates;

    private InMemoryAuditTrail $audit;

    private TrainingActorProvider $actors;

    private DelegateDocument $delegate;

    private UpdateDocument $update;

    private TrainingAlertsQuery $alerts;

    private FixedClock $clock;

    private int $superAdmin;

    private int $ketua;

    private int $sekretaris;

    private int $anggota;

    private int $training;

    protected function setUp(): void
    {
        $this->trainings = new InMemoryTrainingRepository;
        $teams = new InMemoryWorkTeamRepository;
        $users = new InMemoryUserAccountRepository;
        $this->candidates = new InMemoryAssignmentCandidateRepository;
        $this->assignments = new InMemoryAssignmentRepository($this->trainings, $teams, $this->candidates);
        $this->documents = new InMemoryAdministrativeDocumentRepository;
        $this->audit = new InMemoryAuditTrail;
        $this->clock = new FixedClock(new DateTimeImmutable('2026-12-12 09:00'));

        $this->superAdmin = $users->seed('999900000000000931', Role::SuperAdmin);
        $this->ketua = $users->seed('999900000000000932');
        $this->sekretaris = $users->seed('999900000000000933');
        $this->anggota = $users->seed('999900000000000934');

        $team = $teams->addTeam(WorkTeamCode::Dta);
        $teams->members[$team] = [$this->ketua => TeamRole::Ketua, $this->sekretaris => TeamRole::Sekretaris, $this->anggota => TeamRole::Anggota];

        $snapshot = new SopSnapshot(1, 2026, 1, 'SOP/DTA', 'https://drive.example.test/sop', AdministrativeDeadlines::of(1, 2, 2, 3), false);
        $this->training = $this->trainings->insert(TrainingBuilder::draft(teamId: $team)->finalize($snapshot, new DateTimeImmutable('2026-11-20')), 1);
        $this->documents->openMissing($this->training);

        $this->actors = new TrainingActorProvider($users, $teams, $this->assignments);
        $access = new DocumentAccess($this->documents, $this->trainings, $this->actors);
        $this->delegate = new DelegateDocument($access, $this->documents, $teams, $this->audit, new ImmediateTransaction);
        $this->update = new UpdateDocument($access, $this->documents, $this->audit, new ImmediateTransaction);
        $this->alerts = new TrainingAlertsQuery($this->documents, $this->assignments, new TrainingWorkload($this->assignments), new GlobalSettings(new InMemorySettingsRepository), $this->clock);
    }

    private function documentId(DocumentType $type): int
    {
        foreach ($this->documents->forTraining($this->training) as $document) {
            if ($document->type === $type) {
                return (int) $document->id;
            }
        }

        $this->fail('Dokumen tidak ditemukan.');
    }

    private function done(): DocumentInput
    {
        return new DocumentInput('selesai', 'SK/12/2026', '2026-12-12', 'https://drive.example.test/sk');
    }

    public function test_ketua_delegates_to_team_secretary_who_can_then_update(): void
    {
        $sk = $this->documentId(DocumentType::Sk);

        try {
            $this->update->handle($this->sekretaris, $sk, $this->done());
            $this->fail('Sekretaris yang belum ditugaskan seharusnya ditolak.');
        } catch (TrainingAccessDenied) {
        }

        $this->delegate->handle($this->ketua, $sk, $this->sekretaris);
        $this->delegate->handle($this->ketua, $sk, $this->sekretaris);

        $this->assertCount(1, $this->audit->events);
        $this->assertSame(AuditEventType::DocumentDelegated, $this->audit->events[0]->type);
        $this->assertSame($this->sekretaris, $this->audit->events[0]->context['new_assignee_id']);

        $this->assertTrue($this->update->handle($this->sekretaris, $sk, $this->done()));
        $this->assertFalse($this->update->handle($this->sekretaris, $sk, $this->done()));

        $event = $this->audit->events[1];
        $this->assertSame(AuditEventType::DocumentUpdated, $event->type);
        $this->assertSame('belum', $event->context['old_status']);
        $this->assertSame('selesai', $event->context['new_status']);

        // Sekretaris tidak dapat memperbarui dokumen lain yang tidak didelegasikan kepadanya.
        $this->expectException(TrainingAccessDenied::class);
        $this->update->handle($this->sekretaris, $this->documentId(DocumentType::Bap), $this->done());
    }

    public function test_only_ketua_and_super_admin_delegate_and_only_to_secretary(): void
    {
        $bap = $this->documentId(DocumentType::Bap);

        foreach ([$this->sekretaris, $this->anggota] as $actor) {
            try {
                $this->delegate->handle($actor, $bap, $this->sekretaris);
                $this->fail('Selain Ketua/Super Admin seharusnya ditolak.');
            } catch (TrainingAccessDenied) {
            }
        }

        try {
            $this->delegate->handle($this->superAdmin, $bap, $this->anggota);
            $this->fail('Delegasi ke Anggota seharusnya ditolak.');
        } catch (InvalidDocument) {
        }

        $this->delegate->handle($this->superAdmin, $bap, $this->sekretaris);
        $this->delegate->handle($this->superAdmin, $bap, null);
        $this->assertNull($this->documents->documents[$bap]->assigneeId);

        $this->expectException(TrainingNotFound::class);
        $this->delegate->handle($this->ketua, 999, null);
    }

    public function test_update_validation(): void
    {
        $st = $this->documentId(DocumentType::St);

        foreach ([new DocumentInput('selesai'), new DocumentInput('arsip'), new DocumentInput('dalam_proses', null, '12-12-2026'), new DocumentInput('dalam_proses', null, null, 'javascript:alert(1)')] as $input) {
            try {
                $this->update->handle($this->ketua, $st, $input);
                $this->fail('Isian tidak valid seharusnya ditolak.');
            } catch (BusinessRuleViolation) {
            }
        }

        $this->assertSame(DocumentStatus::Belum, $this->documents->documents[$st]->status);
        $this->assertTrue($this->update->handle($this->ketua, $st, new DocumentInput('dalam_proses', 'ST/1')));
    }

    public function test_alerts_and_dashboard(): void
    {
        $training = $this->trainings->find($this->training);
        $this->assertNotNull($training);

        // 12 Des: BAP (H+1) lewat, SK/ST (H+2) jatuh hari ini, link peserta kosong.
        $kinds = array_map(fn ($alert) => $alert->kind, $this->alerts->for($training));
        $this->assertSame([AlertKind::DocumentOverdue, AlertKind::ParticipantListMissing, AlertKind::DocumentDueToday, AlertKind::DocumentDueToday], $kinds);

        $this->delegate->handle($this->ketua, $this->documentId(DocumentType::Bap), $this->sekretaris);
        $this->candidates->add($this->anggota, 'Admin', canBeAdmin: true);
        $this->candidates->add($this->ketua, 'Ketua');
        (new UpdateTrainingAssignments($this->trainings, $this->assignments, $this->candidates, $this->actors, $this->audit, new ImmediateTransaction))
            ->handle($this->ketua, $this->training, [$this->anggota], [], null);

        $dashboard = new DashboardOverview($this->trainings, $this->documents, $this->assignments, $this->alerts, $this->clock);

        $mine = $dashboard->for($this->sekretaris);
        $this->assertSame(['running' => 1, 'incomplete' => 1, 'closing_soon' => 0, 'conflicts' => 0], $mine['counts']);
        $this->assertCount(1, $mine['my_documents']);
        $this->assertSame('2026-12-11', $mine['my_documents'][0]['deadline']?->format('Y-m-d'));
        $this->assertSame([], $mine['my_missing_links']);

        $admin = $dashboard->for($this->anggota);
        $this->assertSame([], $admin['my_documents']);
        $this->assertSame([$this->training], array_map(fn ($t) => $t->id, $admin['my_missing_links']));

        $this->clock = new FixedClock(new DateTimeImmutable('2027-01-01'));
        $later = new DashboardOverview($this->trainings, $this->documents, $this->assignments, $this->alerts, $this->clock);
        $this->assertSame([], $later->for($this->sekretaris)['running']);
    }
}
