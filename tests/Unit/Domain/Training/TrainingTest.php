<?php

namespace Tests\Unit\Domain\Training;

use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Training\InvalidTraining;
use App\Domain\Training\SopSnapshot;
use App\Domain\Training\Training;
use App\Domain\Training\TrainingDetails;
use App\Domain\Training\TrainingIncomplete;
use App\Domain\Training\TrainingLocked;
use App\Domain\Training\TrainingPhase;
use App\Domain\Training\TrainingSchedule;
use App\Domain\Training\TrainingStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\TrainingBuilder;

final class TrainingTest extends TestCase
{
    private function snapshot(): SopSnapshot
    {
        return new SopSnapshot(7, 2026, 1, 'SOP/DTA/2026', 'https://drive.example.test/sop', AdministrativeDeadlines::of(1, 2, 2, 3), false);
    }

    public function test_partial_draft_can_be_created_but_not_finalized(): void
    {
        $draft = Training::draft(1, new TrainingDetails('Draft parsial', TrainingSchedule::empty()), null, new DateTimeImmutable('2026-11-04'));

        $this->assertSame(TrainingStatus::Draft, $draft->status);
        $this->assertNull($draft->phaseOn(new DateTimeImmutable('2026-11-04')));

        try {
            $draft->finalize(null, new DateTimeImmutable('2026-11-04'));
            $this->fail('Draft belum lengkap seharusnya ditolak.');
        } catch (TrainingIncomplete $exception) {
            $this->assertArrayHasKey('registration_url', $exception->missing);
            $this->assertStringContainsString('link pendaftaran', $exception->getMessage());
        }
    }

    public function test_finalize_stores_snapshot_and_locks_status(): void
    {
        $now = new DateTimeImmutable('2026-11-05 10:00');
        $final = TrainingBuilder::draft(id: 3)->finalize($this->snapshot(), $now);

        $this->assertTrue($final->isFinal());
        $this->assertSame(7, $final->sopSnapshot?->provisionId);
        $this->assertSame($now, $final->finalizedAt);
        $this->assertSame(TrainingPhase::AkanDibuka, $final->phaseOn($now));

        $this->expectException(TrainingLocked::class);
        $final->finalize($this->snapshot(), $now);
    }

    public function test_finalize_without_provision_is_allowed(): void
    {
        $final = TrainingBuilder::draft()->finalize(null, new DateTimeImmutable('2026-11-05'));

        $this->assertTrue($final->isFinal());
        $this->assertNull($final->sopSnapshot);
    }

    public function test_participant_list_link_only_after_registration_ends(): void
    {
        $withLink = TrainingBuilder::completeDetails(participantListUrl: 'https://docs.example.test/peserta');

        try {
            Training::draft(1, $withLink, null, new DateTimeImmutable('2026-12-10 20:00'));
            $this->fail('Link daftar peserta seharusnya ditolak selama pendaftaran berjalan.');
        } catch (InvalidTraining $exception) {
            $this->assertSame('participant_list_url', $exception->field());
        }

        $final = TrainingBuilder::draft(id: 1)->finalize($this->snapshot(), new DateTimeImmutable('2026-11-05'));
        $completed = $final->revise(1, $withLink, null, new DateTimeImmutable('2026-12-11'));
        $this->assertSame('https://docs.example.test/peserta', $completed->details->participantListUrl?->value);

        // Nilai yang sama tidak diperiksa ulang (mis. tanggal pendaftaran diperpanjang kemudian).
        $completed->revise(1, $withLink, null, new DateTimeImmutable('2026-12-01'));
    }

    public function test_draft_can_change_team_but_final_cannot(): void
    {
        $draft = TrainingBuilder::draft(teamId: 1, id: 1);
        $this->assertSame(2, $draft->revise(2, $draft->details, null, new DateTimeImmutable('2026-11-04'))->teamId);

        $final = $draft->finalize(null, new DateTimeImmutable('2026-11-04'));

        try {
            $final->revise(2, $final->details, null, new DateTimeImmutable('2026-11-04'));
            $this->fail('Tim kerja pelatihan Final seharusnya terkunci.');
        } catch (InvalidTraining $exception) {
            $this->assertSame('work_team_id', $exception->field());
        }
    }

    public function test_final_training_must_stay_complete_and_keeps_snapshot(): void
    {
        $final = TrainingBuilder::draft(id: 1)->finalize($this->snapshot(), new DateTimeImmutable('2026-11-04'));
        $renamed = $final->revise(1, TrainingBuilder::completeDetails(name: 'Nama baru'), null, new DateTimeImmutable('2026-11-06'));

        $this->assertSame('Nama baru', $renamed->details->name);
        $this->assertSame(7, $renamed->sopSnapshot?->provisionId);
        $this->assertTrue($renamed->isFinal());

        $this->expectException(TrainingIncomplete::class);
        $final->revise(1, new TrainingDetails('Dikosongkan', TrainingSchedule::empty()), null, new DateTimeImmutable('2026-11-06'));
    }

    public function test_learning_path_requires_level(): void
    {
        $this->expectException(InvalidTraining::class);
        Training::draft(1, new TrainingDetails('Tanpa level', TrainingSchedule::empty()), 5, new DateTimeImmutable('2026-11-04'));
    }
}
