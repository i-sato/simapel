<?php

namespace Tests\Unit\Application\Training;

use App\Application\Organization\Exceptions\WorkTeamNotFound;
use App\Application\Sop\ResolveSopProvision;
use App\Application\Training\CreateTrainingDraft;
use App\Application\Training\Exceptions\LearningPathNotFound;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\Exceptions\TrainingNotFound;
use App\Application\Training\FinalizeTraining;
use App\Application\Training\LearningPathPlacement;
use App\Application\Training\TrainingAccess;
use App\Application\Training\TrainingActorProvider;
use App\Application\Training\TrainingPreparation;
use App\Application\Training\UpdateTraining;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Role;
use App\Domain\Organization\TeamRole;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Shared\BusinessRuleViolation;
use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Sop\SopProvision;
use App\Domain\Training\InvalidTraining;
use App\Domain\Training\TrainingIncomplete;
use App\Domain\Training\TrainingStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\FixedClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemoryLearningPathRepository;
use Tests\Support\Fakes\InMemorySopProvisionRepository;
use Tests\Support\Fakes\InMemoryTrainingRepository;
use Tests\Support\Fakes\InMemoryUserAccountRepository;
use Tests\Support\Fakes\InMemoryWorkTeamRepository;
use Tests\Support\SopProvisionBuilder;
use Tests\Support\TrainingBuilder;

final class TrainingUseCasesTest extends TestCase
{
    private InMemoryTrainingRepository $trainings;

    private InMemoryLearningPathRepository $paths;

    private InMemoryWorkTeamRepository $teams;

    private InMemorySopProvisionRepository $provisions;

    private InMemoryAuditTrail $audit;

    private CreateTrainingDraft $create;

    private UpdateTraining $update;

    private FinalizeTraining $finalize;

    private TrainingAccess $access;

    private int $superAdmin;

    private int $ketuaDta;

    private int $employee;

    private int $dta;

    private int $kdd;

    protected function setUp(): void
    {
        $this->trainings = new InMemoryTrainingRepository;
        $this->paths = new InMemoryLearningPathRepository;
        $this->teams = new InMemoryWorkTeamRepository;
        $this->provisions = new InMemorySopProvisionRepository;
        $this->audit = new InMemoryAuditTrail;
        $users = new InMemoryUserAccountRepository;
        $transaction = new ImmediateTransaction;
        $clock = new FixedClock(new DateTimeImmutable('2026-11-04 09:00'));

        $this->superAdmin = $users->seed('999900000000000901', Role::SuperAdmin);
        $this->ketuaDta = $users->seed('999900000000000902');
        $this->employee = $users->seed('999900000000000903');
        $this->dta = $this->teams->addTeam(WorkTeamCode::Dta);
        $this->kdd = $this->teams->addTeam(WorkTeamCode::Kdd);
        $this->teams->members[$this->dta][$this->ketuaDta] = TeamRole::Ketua;

        $actors = new TrainingActorProvider($users, $this->teams);
        $placement = new LearningPathPlacement($this->paths, $this->trainings, $this->audit);
        $resolve = new ResolveSopProvision($this->provisions, $clock);
        $preparation = new TrainingPreparation($resolve, $this->teams, $clock);

        $this->create = new CreateTrainingDraft($this->trainings, $this->teams, $actors, $placement, $this->audit, $transaction, $clock);
        $this->update = new UpdateTraining($this->trainings, $this->teams, $actors, $placement, $this->audit, $transaction, $clock);
        $this->finalize = new FinalizeTraining($this->trainings, $this->teams, $actors, $resolve, $preparation, $this->audit, $transaction, $clock);
        $this->access = new TrainingAccess($actors, $this->trainings);
    }

    private function activeProvision(WorkTeamCode $team, int $year, int $readyDays = 3): int
    {
        $id = $this->provisions->insert(SopProvisionBuilder::draft(year: $year, from: $year.'-01-01', team: $team), 1);
        $this->provisions->provisions[$id] = $this->provisions->provisions[$id]->activate();

        if ($readyDays !== 3) {
            $provision = $this->provisions->provisions[$id];
            $this->provisions->provisions[$id] = SopProvision::restore(
                $id, $provision->team, $provision->fiscalYear, $provision->version, $provision->status, $provision->sop,
                $provision->effectiveFrom, null, AdministrativeDeadlines::of(1, 1, 1, $readyDays), null,
            );
        }

        return $id;
    }

    public function test_ketua_creates_draft_in_own_team_with_audit(): void
    {
        $id = $this->create->handle($this->ketuaDta, $this->dta, TrainingBuilder::input(['level' => null, 'batch' => null]));

        $draft = $this->trainings->trainings[$id];
        $this->assertSame(TrainingStatus::Draft, $draft->status);
        $this->assertSame(AuditEventType::TrainingDrafted, $this->audit->events[0]->type);
        $this->assertSame($this->dta, $this->audit->events[0]->context['work_team_id']);
        $this->assertNull($this->audit->events[0]->context['level']);
    }

    public function test_others_cannot_create_drafts(): void
    {
        foreach ([[$this->ketuaDta, $this->kdd], [$this->employee, $this->dta], [999, $this->dta]] as [$actor, $team]) {
            try {
                $this->create->handle($actor, $team, TrainingBuilder::input());
                $this->fail('Pembuatan draft seharusnya ditolak.');
            } catch (TrainingAccessDenied) {
            }
        }

        $this->assertSame([], $this->trainings->trainings);
    }

    public function test_unknown_team_and_invalid_input_are_rejected(): void
    {
        try {
            $this->create->handle($this->superAdmin, 99, TrainingBuilder::input());
            $this->fail('Tim tidak dikenal seharusnya ditolak.');
        } catch (WorkTeamNotFound) {
        }

        foreach ([['registrationEnd' => '2026-11-30'], ['method' => 'teleportasi'], ['registrationUrl' => 'javascript:alert(1)'], ['trainingStart' => '15-12-2026']] as $override) {
            try {
                $this->create->handle($this->superAdmin, $this->dta, TrainingBuilder::input($override));
                $this->fail('Input tidak valid seharusnya ditolak: '.json_encode($override));
            } catch (BusinessRuleViolation) {
            }
        }

        $this->assertSame([], $this->trainings->trainings);
    }

    public function test_learning_path_is_created_and_levels_stay_unique(): void
    {
        $first = $this->create->handle($this->ketuaDta, $this->dta, TrainingBuilder::input(['newLearningPathName' => 'VBL Data']));
        $pathId = $this->trainings->trainings[$first]->learningPathId;

        $this->assertNotNull($pathId);
        $this->assertSame(AuditEventType::LearningPathCreated, $this->audit->events[0]->type);

        $second = $this->create->handle($this->ketuaDta, $this->dta, TrainingBuilder::input(['learningPathId' => $pathId, 'level' => 'menengah']));
        $this->assertSame($pathId, $this->trainings->trainings[$second]->learningPathId);
        $this->assertGreaterThan(0, $this->paths->locks);

        $rejected = [
            ['learningPathId' => $pathId, 'level' => 'dasar'],
            ['learningPathId' => $pathId, 'newLearningPathName' => 'Ganda'],
            ['newLearningPathName' => 'Tanpa level', 'level' => null],
        ];

        foreach ($rejected as $override) {
            try {
                $this->create->handle($this->ketuaDta, $this->dta, TrainingBuilder::input($override));
                $this->fail('Penempatan learning path seharusnya ditolak: '.json_encode($override));
            } catch (InvalidTraining) {
            }
        }

        // Path milik DTA tidak dapat dipakai pelatihan KDD.
        try {
            $this->create->handle($this->superAdmin, $this->kdd, TrainingBuilder::input(['learningPathId' => $pathId, 'level' => 'mahir']));
            $this->fail('Path lintas tim seharusnya ditolak.');
        } catch (InvalidTraining $exception) {
            $this->assertSame('learning_path_id', $exception->field());
        }

        $this->expectException(LearningPathNotFound::class);
        $this->create->handle($this->ketuaDta, $this->dta, TrainingBuilder::input(['learningPathId' => 99]));
    }

    public function test_moving_within_same_path_keeps_own_level(): void
    {
        $id = $this->create->handle($this->ketuaDta, $this->dta, TrainingBuilder::input(['newLearningPathName' => 'VBL']));
        $pathId = $this->trainings->trainings[$id]->learningPathId;

        $this->assertTrue($this->update->handle($this->ketuaDta, $id, $this->dta, TrainingBuilder::input(['learningPathId' => $pathId, 'name' => 'Nama baru'])));
    }

    public function test_ketua_updates_draft_and_changes_are_audited(): void
    {
        $id = $this->create->handle($this->ketuaDta, $this->dta, TrainingBuilder::input());

        $this->assertFalse($this->update->handle($this->ketuaDta, $id, $this->dta, TrainingBuilder::input()));
        $this->assertTrue($this->update->handle($this->ketuaDta, $id, $this->dta, TrainingBuilder::input(['lessonHours' => 40])));

        $event = $this->audit->events[1];
        $this->assertSame(AuditEventType::TrainingUpdated, $event->type);
        $this->assertSame(32, $event->context['old_lesson_hours']);
        $this->assertSame(40, $event->context['new_lesson_hours']);
        $this->assertArrayNotHasKey('old_name', $event->context);
        $this->assertGreaterThan(0, $this->trainings->locks);
    }

    public function test_ketua_cannot_move_draft_to_team_they_do_not_lead(): void
    {
        $id = $this->create->handle($this->ketuaDta, $this->dta, TrainingBuilder::input());

        $this->expectException(TrainingAccessDenied::class);
        $this->update->handle($this->ketuaDta, $id, $this->kdd, TrainingBuilder::input());
    }

    public function test_update_unknown_training(): void
    {
        $this->expectException(TrainingNotFound::class);
        $this->update->handle($this->superAdmin, 99, $this->dta, TrainingBuilder::input());
    }

    public function test_super_admin_finalizes_with_sop_snapshot(): void
    {
        $provisionId = $this->activeProvision(WorkTeamCode::Dta, 2026);
        $id = $this->create->handle($this->ketuaDta, $this->dta, TrainingBuilder::input());

        $this->assertFalse($this->access->canFinalize($this->ketuaDta, $id));
        $this->assertTrue($this->access->canFinalize($this->superAdmin, $id));

        $warnings = $this->finalize->handle($this->superAdmin, $id);

        $final = $this->trainings->trainings[$id];
        $this->assertSame([], $warnings);
        $this->assertTrue($final->isFinal());
        $this->assertSame($provisionId, $final->sopSnapshot?->provisionId);
        $this->assertFalse($final->sopSnapshot->isFallback);
        $this->assertSame(3, $final->sopSnapshot->deadlines->readyDays);
        $event = $this->audit->events[1];
        $this->assertSame(AuditEventType::TrainingFinalized, $event->type);
        $this->assertSame($provisionId, $event->context['sop_provision_id']);
        $this->assertSame(5, $event->context['preparation_days']);

        // Ketua tidak lagi dapat mengubah pelatihan Final; Super Admin dapat melengkapinya.
        $this->assertFalse($this->access->canEdit($this->ketuaDta, $id));
        $this->assertTrue($this->access->canEdit($this->superAdmin, $id));
    }

    public function test_finalize_warns_about_short_gap_and_missing_provision(): void
    {
        $id = $this->create->handle($this->superAdmin, $this->kdd, TrainingBuilder::input());
        $this->assertSame([TrainingPreparation::NO_PROVISION], $this->finalize->handle($this->superAdmin, $id));
        $this->assertNull($this->trainings->trainings[$id]->sopSnapshot);

        $this->activeProvision(WorkTeamCode::Dta, 2025, readyDays: 9);
        $short = $this->create->handle($this->superAdmin, $this->dta, TrainingBuilder::input());
        $warnings = $this->finalize->handle($this->superAdmin, $short);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('H+9', $warnings[0]);
        $this->assertTrue($this->trainings->trainings[$short]->sopSnapshot?->isFallback);
    }

    public function test_finalize_rules(): void
    {
        $partial = $this->create->handle($this->ketuaDta, $this->dta, TrainingBuilder::input(['syllabusUrl' => null]));

        try {
            $this->finalize->handle($this->ketuaDta, $partial);
            $this->fail('Ketua seharusnya tidak dapat memfinalisasi.');
        } catch (TrainingAccessDenied) {
        }

        try {
            $this->finalize->handle($this->superAdmin, $partial);
            $this->fail('Draft belum lengkap seharusnya ditolak.');
        } catch (TrainingIncomplete $exception) {
            $this->assertSame(['syllabus_url' => 'link silabus'], $exception->missing);
        }

        $this->expectException(TrainingNotFound::class);
        $this->finalize->handle($this->superAdmin, 99);
    }

    public function test_preparation_warning_for_draft_uses_provision_in_force_today(): void
    {
        $preparation = new TrainingPreparation(new ResolveSopProvision($this->provisions, new FixedClock(new DateTimeImmutable('2026-11-04'))), $this->teams, new FixedClock(new DateTimeImmutable('2026-11-04')));
        $draft = TrainingBuilder::draft(teamId: $this->dta, id: 1);

        $this->assertSame([TrainingPreparation::NO_PROVISION], $preparation->warningsFor($draft));
        $this->assertSame([TrainingPreparation::NO_PROVISION], $preparation->warningsFor(TrainingBuilder::draft(teamId: 99, id: 2)));

        $this->activeProvision(WorkTeamCode::Dta, 2026, readyDays: 9);
        $this->assertCount(1, $preparation->warningsFor($draft));
    }

    public function test_access_queries(): void
    {
        $this->assertNull($this->access->creatableTeamIds($this->superAdmin));
        $this->assertSame([$this->dta], $this->access->creatableTeamIds($this->ketuaDta));
        $this->assertSame([], $this->access->creatableTeamIds($this->employee));
        $this->assertTrue($this->access->canView($this->employee));
        $this->assertFalse($this->access->canView(999));
        $this->assertTrue($this->access->canCreateIn($this->ketuaDta, $this->dta));
        $this->assertFalse($this->access->canCreateAny($this->employee));
        $this->assertFalse($this->access->canEdit($this->superAdmin, 99));
        $this->assertFalse($this->access->canFinalize($this->superAdmin, 99));
    }
}
