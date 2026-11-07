<?php

namespace Tests\Unit\Application\Assignment;

use App\Application\Assignment\TrainingWorkload;
use App\Application\Assignment\UpdateTrainingAssignments;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\Exceptions\TrainingNotFound;
use App\Application\Training\TrainingAccess;
use App\Application\Training\TrainingActorProvider;
use App\Domain\Assignment\AssignmentConflict;
use App\Domain\Assignment\AssignmentRejected;
use App\Domain\Assignment\AssignmentRole;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Role;
use App\Domain\Organization\TeamRole;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Training\TrainingDetails;
use App\Domain\Training\TrainingSchedule;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAssignmentCandidateRepository;
use Tests\Support\Fakes\InMemoryAssignmentRepository;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemoryTrainingRepository;
use Tests\Support\Fakes\InMemoryUserAccountRepository;
use Tests\Support\Fakes\InMemoryWorkTeamRepository;
use Tests\Support\TrainingBuilder;

final class TrainingAssignmentUseCasesTest extends TestCase
{
    private InMemoryTrainingRepository $trainings;

    private InMemoryAssignmentRepository $assignments;

    private InMemoryAssignmentCandidateRepository $candidates;

    private InMemoryAuditTrail $audit;

    private UpdateTrainingAssignments $update;

    private TrainingAccess $access;

    private int $superAdmin;

    private int $ketuaDta;

    private int $adminCapable;

    private int $employee;

    private int $dtaTraining;

    private int $kddTraining;

    protected function setUp(): void
    {
        $this->trainings = new InMemoryTrainingRepository;
        $teams = new InMemoryWorkTeamRepository;
        $users = new InMemoryUserAccountRepository;
        $this->candidates = new InMemoryAssignmentCandidateRepository;
        $this->assignments = new InMemoryAssignmentRepository($this->trainings, $teams, $this->candidates);
        $this->audit = new InMemoryAuditTrail;

        $this->superAdmin = $users->seed('999900000000000911', Role::SuperAdmin);
        $this->ketuaDta = $users->seed('999900000000000912');
        $this->adminCapable = $users->seed('999900000000000913');
        $this->employee = $users->seed('999900000000000914');
        $this->candidates->add($this->ketuaDta, 'Ketua DTA');
        $this->candidates->add($this->adminCapable, 'Admin Siap', canBeAdmin: true);
        $this->candidates->add($this->employee, 'Pegawai Biasa');

        $dta = $teams->addTeam(WorkTeamCode::Dta);
        $kdd = $teams->addTeam(WorkTeamCode::Kdd);
        $teams->members[$dta][$this->ketuaDta] = TeamRole::Ketua;

        // Pelatihan DTA 1–17 Des dan KDD 17–20 Des: bersinggungan pada 17 Des.
        $this->dtaTraining = $this->trainings->insert(TrainingBuilder::draft(teamId: $dta, details: TrainingBuilder::completeDetails(name: 'Pelatihan DTA')), 1);
        $this->kddTraining = $this->trainings->insert(TrainingBuilder::draft(teamId: $kdd, details: TrainingBuilder::completeDetails(
            schedule: TrainingBuilder::schedule('2026-12-17', '2026-12-18', '2026-12-19', '2026-12-20'),
            name: 'Pelatihan KDD',
        )), 1);

        $actors = new TrainingActorProvider($users, $teams, $this->assignments);
        $this->update = new UpdateTrainingAssignments($this->trainings, $this->assignments, $this->candidates, $actors, $this->audit, new ImmediateTransaction);
        $this->access = new TrainingAccess($actors, $this->trainings);
    }

    public function test_ketua_assigns_admin_and_panitia_with_audit(): void
    {
        $plan = $this->update->handle($this->ketuaDta, $this->dtaTraining, [$this->adminCapable], [$this->employee, $this->ketuaDta], null);

        $this->assertCount(3, $plan->added);
        $this->assertCount(3, $this->assignments->forTraining($this->dtaTraining));
        $this->assertSame(AuditEventType::TrainingAssignmentAdded, $this->audit->events[0]->type);
        $this->assertSame('admin', $this->audit->events[0]->context['role']);
        $this->assertNull($this->audit->events[0]->context['force_reason']);
        $this->assertGreaterThan(0, $this->trainings->locks);

        $this->update->handle($this->ketuaDta, $this->dtaTraining, [$this->adminCapable], [$this->employee], null);
        $removed = $this->audit->events[3];
        $this->assertSame(AuditEventType::TrainingAssignmentRemoved, $removed->type);
        $this->assertSame($this->ketuaDta, $removed->context['employee_id']);
    }

    public function test_only_team_ketua_and_super_admin_manage_assignments(): void
    {
        $this->assertTrue($this->access->canManageAssignments($this->superAdmin, $this->kddTraining));
        $this->assertFalse($this->access->canManageAssignments($this->ketuaDta, $this->kddTraining));
        $this->assertFalse($this->access->canManageAssignments($this->superAdmin, 99));

        try {
            $this->update->handle($this->ketuaDta, $this->kddTraining, [], [$this->employee], null);
            $this->fail('Ketua tim lain seharusnya ditolak.');
        } catch (TrainingAccessDenied) {
        }

        try {
            $this->update->handle($this->employee, $this->dtaTraining, [], [$this->employee], null);
            $this->fail('Pegawai biasa seharusnya ditolak.');
        } catch (TrainingAccessDenied) {
        }

        $this->expectException(TrainingNotFound::class);
        $this->update->handle($this->superAdmin, 99, [], [], null);
    }

    public function test_admin_requires_capability(): void
    {
        $this->expectException(AssignmentRejected::class);
        $this->update->handle($this->superAdmin, $this->dtaTraining, [$this->employee], [], null);
    }

    public function test_conflict_requires_force_reason_across_teams(): void
    {
        $this->update->handle($this->superAdmin, $this->kddTraining, [], [$this->employee], null);

        try {
            $this->update->handle($this->ketuaDta, $this->dtaTraining, [], [$this->employee], null);
            $this->fail('Bentrok tanpa alasan seharusnya ditolak.');
        } catch (AssignmentConflict $exception) {
            $this->assertStringContainsString('Panitia KDD · 17–20 Des "Pelatihan KDD"', $exception->getMessage());
        }

        $this->assertSame([], $this->assignments->forTraining($this->dtaTraining));

        $this->update->handle($this->ketuaDta, $this->dtaTraining, [], [$this->employee], 'Hanya dia yang menguasai materi');

        $saved = array_values($this->assignments->assignments)[1] ?? null;
        $this->assertSame($this->dtaTraining, $saved?->trainingId);
        $this->assertSame('Hanya dia yang menguasai materi', $saved->forceReason);
        $event = $this->audit->events[count($this->audit->events) - 1];
        $this->assertSame('Hanya dia yang menguasai materi', $event->context['force_reason']);
        $this->assertSame((string) $this->kddTraining, $event->context['conflict_training_ids']);

        $workload = new TrainingWorkload($this->assignments);
        $dta = $this->trainings->find($this->dtaTraining);
        $this->assertNotNull($dta);
        $this->assertSame([$this->employee], array_keys($workload->conflictsFor($dta)));
        $this->assertSame([], $workload->conflictsFor($dta, [$this->adminCapable]));
    }

    public function test_training_without_complete_schedule_has_no_conflicts(): void
    {
        $partial = $this->trainings->insert(TrainingBuilder::draft(teamId: 1, details: new TrainingDetails('Draft tanpa jadwal', TrainingSchedule::empty())), 1);
        $this->update->handle($this->superAdmin, $this->kddTraining, [], [$this->employee], null);

        $this->update->handle($this->superAdmin, $partial, [], [$this->employee], null);

        $this->assertCount(1, $this->assignments->forTraining($partial));
        $training = $this->trainings->find($partial);
        $this->assertNotNull($training);
        $this->assertSame([], (new TrainingWorkload($this->assignments))->conflictsFor($training));
    }

    public function test_assigned_admin_can_finalize_and_complete_but_not_manage_assignments(): void
    {
        $this->assertFalse($this->access->canFinalize($this->adminCapable, $this->dtaTraining));

        $this->update->handle($this->ketuaDta, $this->dtaTraining, [$this->adminCapable], [], null);

        $this->assertTrue($this->access->canFinalize($this->adminCapable, $this->dtaTraining));
        $this->assertTrue($this->access->canEdit($this->adminCapable, $this->dtaTraining));
        $this->assertFalse($this->access->canFinalize($this->adminCapable, $this->kddTraining));
        $this->assertFalse($this->access->canManageAssignments($this->adminCapable, $this->dtaTraining));

        // Tanda "dapat menjadi Admin" dicabut: hak Admin pada pelatihan ikut hilang.
        $this->candidates->add($this->adminCapable, 'Admin Siap', canBeAdmin: false);
        $this->assertFalse($this->access->canFinalize($this->adminCapable, $this->dtaTraining));
        $this->assertSame(AssignmentRole::Admin, $this->assignments->forTraining($this->dtaTraining)[0]->role);
    }
}
