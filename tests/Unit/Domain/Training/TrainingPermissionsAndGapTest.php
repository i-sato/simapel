<?php

namespace Tests\Unit\Domain\Training;

use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Training\PreparationGap;
use App\Domain\Training\TrainingActor;
use App\Domain\Training\TrainingPermissions;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\TrainingBuilder;

final class TrainingPermissionsAndGapTest extends TestCase
{
    public function test_ketua_creates_and_edits_drafts_of_own_team_only(): void
    {
        $ketua = new TrainingActor(5, true, false, ledTeamIds: [1]);
        $draft = TrainingBuilder::draft(teamId: 1, id: 9);
        $otherTeam = TrainingBuilder::draft(teamId: 2, id: 10);

        $this->assertTrue(TrainingPermissions::canCreateAny($ketua));
        $this->assertTrue(TrainingPermissions::canCreateIn($ketua, 1));
        $this->assertFalse(TrainingPermissions::canCreateIn($ketua, 2));
        $this->assertTrue(TrainingPermissions::canEdit($ketua, $draft));
        $this->assertFalse(TrainingPermissions::canEdit($ketua, $otherTeam));
        $this->assertFalse(TrainingPermissions::canFinalize($ketua, $draft));
        $this->assertFalse(TrainingPermissions::canEdit($ketua, $draft->finalize(null, new DateTimeImmutable('2026-11-04'))));
    }

    public function test_super_admin_finalizes_and_completes_final_trainings(): void
    {
        $admin = new TrainingActor(1, true, true);
        $draft = TrainingBuilder::draft(teamId: 3, id: 9);
        $final = $draft->finalize(null, new DateTimeImmutable('2026-11-04'));

        $this->assertTrue(TrainingPermissions::canCreateIn($admin, 3));
        $this->assertTrue(TrainingPermissions::canFinalize($admin, $draft));
        $this->assertFalse(TrainingPermissions::canFinalize($admin, $final));
        $this->assertTrue(TrainingPermissions::canEdit($admin, $final));
    }

    public function test_assigned_admin_extension_point(): void
    {
        $admin = new TrainingActor(8, true, false, assignedAdminTrainingIds: [9]);
        $draft = TrainingBuilder::draft(teamId: 3, id: 9);

        $this->assertTrue(TrainingPermissions::canFinalize($admin, $draft));
        $this->assertTrue(TrainingPermissions::canEdit($admin, $draft->finalize(null, new DateTimeImmutable('2026-11-04'))));
        $this->assertFalse(TrainingPermissions::canFinalize($admin, TrainingBuilder::draft(teamId: 3, id: 10)));
    }

    public function test_plain_or_inactive_employee_only_views(): void
    {
        $employee = new TrainingActor(4, true, false);
        $inactiveAdmin = new TrainingActor(2, false, true, ledTeamIds: [1]);
        $draft = TrainingBuilder::draft(teamId: 1, id: 9);

        $this->assertTrue(TrainingPermissions::canView($employee));
        $this->assertFalse(TrainingPermissions::canCreateAny($employee));
        $this->assertFalse(TrainingPermissions::canEdit($employee, $draft));
        $this->assertFalse(TrainingPermissions::canView($inactiveAdmin));
        $this->assertFalse(TrainingPermissions::canCreateAny($inactiveAdmin));
        $this->assertFalse(TrainingPermissions::canEdit($inactiveAdmin, $draft));
        $this->assertFalse(TrainingPermissions::canFinalize($inactiveAdmin, $draft));
    }

    public function test_preparation_gap_warns_when_shorter_than_ready_deadline(): void
    {
        $schedule = TrainingBuilder::schedule(); // jeda H+5

        $this->assertNull(PreparationGap::warning($schedule, AdministrativeDeadlines::of(1, 2, 2, 3)));
        $this->assertNull(PreparationGap::warning($schedule, AdministrativeDeadlines::of(4, 4, 4, 5)));
        $this->assertStringContainsString('hanya 5 hari', (string) PreparationGap::warning($schedule, AdministrativeDeadlines::of(5, 6, 6, 9)));
        $this->assertNull(PreparationGap::warning(TrainingBuilder::schedule(null, null, null, null), AdministrativeDeadlines::of(5, 6, 6, 9)));
    }
}
