<?php

namespace Tests\Unit\Domain\Administration;

use App\Domain\Administration\AdministrativeDocument;
use App\Domain\Administration\AlertKind;
use App\Domain\Administration\DocumentStatus;
use App\Domain\Administration\DocumentType;
use App\Domain\Administration\TrainingAlert;
use App\Domain\Administration\TrainingAlertInput;
use App\Domain\Administration\TrainingAlerts;
use App\Domain\Shared\HttpUrl;
use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Training\TrainingSchedule;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\TrainingBuilder;

/**
 * Jadwal bawaan: pendaftaran 1–10 Des 2026, pelatihan 15–17 Des 2026.
 * Ketentuan DTA: BAP H+1 (11 Des), SK/ST H+2 (12 Des), administrasi siap H+3 (13 Des).
 */
final class TrainingAlertsTest extends TestCase
{
    /**
     * @param  array<string, DocumentStatus>  $statuses
     * @return list<AdministrativeDocument>
     */
    private static function documents(array $statuses = []): array
    {
        $documents = [];

        foreach (DocumentType::cases() as $index => $type) {
            $status = $statuses[$type->value] ?? DocumentStatus::Belum;
            $document = AdministrativeDocument::restore($index + 1, 9, $type, DocumentStatus::Belum, null, null, null, null);
            $documents[] = $status === DocumentStatus::Selesai
                ? $document->record($status, 'NO/'.$index, new DateTimeImmutable('2026-12-11'), HttpUrl::fromString('https://drive.example.test/'.$index))
                : $document->record($status, null, null, null);
        }

        return $documents;
    }

    /**
     * @param  list<AdministrativeDocument>|null  $documents
     */
    private static function input(
        ?AdministrativeDeadlines $deadlines = null,
        ?array $documents = null,
        bool $isFinal = true,
        bool $hasParticipantList = true,
        int $closingSoonDays = 7,
        int $conflicts = 0,
        ?TrainingSchedule $schedule = null,
    ): TrainingAlertInput {
        return new TrainingAlertInput(
            isFinal: $isFinal,
            schedule: $schedule ?? TrainingBuilder::schedule(),
            deadlines: $deadlines ?? AdministrativeDeadlines::of(1, 2, 2, 3),
            documents: $documents ?? self::documents(),
            hasParticipantList: $hasParticipantList,
            closingSoonDays: $closingSoonDays,
            conflictCount: $conflicts,
        );
    }

    /**
     * @param  list<TrainingAlert>  $alerts
     * @return list<string>
     */
    private static function summary(array $alerts): array
    {
        return array_map(fn (TrainingAlert $alert): string => $alert->kind->value.($alert->document ? ':'.$alert->document->value : ''), $alerts);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function documentTimeline(): iterable
    {
        yield 'sebelum tenggat (hari akhir pendaftaran)' => ['2026-12-10 23:00', []];
        yield 'hari tenggat BAP' => ['2026-12-11 00:00', ['mendekati_tenggat:bap']];
        yield 'BAP lewat, hari tenggat SK/ST' => ['2026-12-12', ['lewat_tenggat:bap', 'mendekati_tenggat:sk', 'mendekati_tenggat:st']];
        yield 'hari tenggat administrasi siap (belum kritis)' => ['2026-12-13', ['lewat_tenggat:bap', 'lewat_tenggat:sk', 'lewat_tenggat:st']];
        yield 'sehari setelah administrasi siap (kritis)' => ['2026-12-14', ['kritis', 'lewat_tenggat:bap', 'lewat_tenggat:sk', 'lewat_tenggat:st']];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('documentTimeline')]
    public function test_document_deadlines_use_snapshot_from_registration_end(string $now, array $expected): void
    {
        $this->assertSame($expected, self::summary(TrainingAlerts::for(self::input(), new DateTimeImmutable($now))));
    }

    public function test_critical_when_training_started_even_before_ready_deadline(): void
    {
        // KDD: administrasi siap H+9 (19 Des) melewati awal pelatihan 15 Des.
        $kdd = AdministrativeDeadlines::of(5, 6, 6, 9);

        $this->assertNotContains('kritis', self::summary(TrainingAlerts::for(self::input($kdd), new DateTimeImmutable('2026-12-14'))));
        $alerts = TrainingAlerts::for(self::input($kdd), new DateTimeImmutable('2026-12-15'));
        $this->assertSame(AlertKind::AdministrationCritical, $alerts[0]->kind);
        $this->assertStringContainsString('pelatihan sudah dimulai', $alerts[0]->detail);
    }

    public function test_completed_documents_raise_no_alert(): void
    {
        $done = self::documents(['bap' => DocumentStatus::Selesai, 'sk' => DocumentStatus::Selesai, 'st' => DocumentStatus::Selesai]);
        $this->assertSame([], TrainingAlerts::for(self::input(documents: $done), new DateTimeImmutable('2026-12-14')));

        $partial = self::documents(['bap' => DocumentStatus::Selesai, 'sk' => DocumentStatus::DalamProses]);
        $alerts = TrainingAlerts::for(self::input(documents: $partial), new DateTimeImmutable('2026-12-14'));
        $this->assertSame(['kritis', 'lewat_tenggat:sk', 'lewat_tenggat:st'], self::summary($alerts));
        $this->assertStringContainsString('SK, ST belum selesai', $alerts[0]->detail);
    }

    public function test_final_without_snapshot_shows_deadline_not_set_and_drafts_have_no_document_alerts(): void
    {
        $missing = new TrainingAlertInput(true, TrainingBuilder::schedule(), null, self::documents(), true, 7, 0);
        $this->assertSame(['tenggat_belum_ditetapkan'], self::summary(TrainingAlerts::for($missing, new DateTimeImmutable('2026-12-14'))));

        $this->assertSame([], TrainingAlerts::for(self::input(isFinal: false, documents: []), new DateTimeImmutable('2026-12-14')));
    }

    public function test_participant_list_missing_after_registration_closes(): void
    {
        $draft = fn (string $now): array => self::summary(TrainingAlerts::for(self::input(isFinal: false, documents: [], hasParticipantList: false), new DateTimeImmutable($now)));

        $this->assertSame([], $draft('2026-12-10 23:59'));
        $this->assertSame(['link_peserta_kosong'], $draft('2026-12-11 00:00'));
        $this->assertSame([], self::summary(TrainingAlerts::for(self::input(isFinal: false, documents: []), new DateTimeImmutable('2026-12-11'))));
    }

    /**
     * @return iterable<string, array{string, int, bool}>
     */
    public static function closingSoon(): iterable
    {
        yield 'belum dimulai' => ['2026-12-14', 7, false];
        yield 'hari pertama, sisa 2 hari' => ['2026-12-15', 7, true];
        yield 'hari terakhir' => ['2026-12-17 22:00', 7, true];
        yield 'sudah selesai' => ['2026-12-18', 7, false];
        yield 'ambang 1 hari, sisa 2 hari' => ['2026-12-15', 1, false];
        yield 'ambang 1 hari, sisa 1 hari' => ['2026-12-16', 1, true];
    }

    #[DataProvider('closingSoon')]
    public function test_closing_soon_uses_configured_threshold(string $now, int $days, bool $expected): void
    {
        $alerts = TrainingAlerts::for(self::input(isFinal: false, documents: [], closingSoonDays: $days), new DateTimeImmutable($now));

        $this->assertSame($expected, in_array('segera_berakhir', self::summary($alerts), true));
    }

    public function test_closing_soon_message_and_conflicts(): void
    {
        $alerts = TrainingAlerts::for(self::input(isFinal: false, documents: [], conflicts: 2), new DateTimeImmutable('2026-12-17'));

        $this->assertSame(['bentrok_penugasan', 'segera_berakhir'], self::summary($alerts));
        $this->assertSame('Pelatihan berakhir hari ini.', $alerts[1]->detail);
        $this->assertStringContainsString('2 pegawai', $alerts[0]->detail);
    }

    public function test_deadlines_cross_year_boundary(): void
    {
        $schedule = TrainingBuilder::schedule('2026-12-20', '2026-12-31', '2027-01-04', '2027-01-08');

        $this->assertSame(['mendekati_tenggat:bap'], self::summary(TrainingAlerts::for(self::input(schedule: $schedule), new DateTimeImmutable('2027-01-01'))));
        $this->assertContains('kritis', self::summary(TrainingAlerts::for(self::input(schedule: $schedule), new DateTimeImmutable('2027-01-04'))));
    }
}
