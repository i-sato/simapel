<?php

namespace Tests\Unit\Application\Messaging;

use App\Application\Assignment\UpdateTrainingAssignments;
use App\Application\Messaging\ResetCustomTemplate;
use App\Application\Messaging\SaveCustomTemplate;
use App\Application\Messaging\TrainingMessages;
use App\Application\Messaging\TrainingMessageValues;
use App\Application\Settings\GlobalSettings;
use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Application\Training\Exceptions\TrainingNotFound;
use App\Application\Training\TrainingActorProvider;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\Role;
use App\Domain\Messaging\DefaultTemplate;
use App\Domain\Messaging\InvalidMessageTemplate;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Training\TrainingLevel;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAssignmentCandidateRepository;
use Tests\Support\Fakes\InMemoryAssignmentRepository;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemoryCustomTemplateRepository;
use Tests\Support\Fakes\InMemorySettingsRepository;
use Tests\Support\Fakes\InMemoryTrainingRepository;
use Tests\Support\Fakes\InMemoryUserAccountRepository;
use Tests\Support\Fakes\InMemoryWorkTeamRepository;
use Tests\Support\TrainingBuilder;
use Tests\Unit\Domain\Messaging\TemplateBodyTest;

final class MessageTemplateUseCasesTest extends TestCase
{
    private InMemoryTrainingRepository $trainings;

    private InMemoryCustomTemplateRepository $templates;

    private InMemoryAuditTrail $audit;

    private TrainingMessages $messages;

    private SaveCustomTemplate $save;

    private ResetCustomTemplate $reset;

    private int $panitia;

    private int $outsider;

    private int $training;

    protected function setUp(): void
    {
        $this->trainings = new InMemoryTrainingRepository;
        $teams = new InMemoryWorkTeamRepository;
        $users = new InMemoryUserAccountRepository;
        $candidates = new InMemoryAssignmentCandidateRepository;
        $assignments = new InMemoryAssignmentRepository($this->trainings, $teams, $candidates);
        $settings = new InMemorySettingsRepository;
        $settings->values['digitalent_login_url'] = 'https://digitalent.example.test/login';
        $this->templates = new InMemoryCustomTemplateRepository;
        $this->audit = new InMemoryAuditTrail;

        $superAdmin = $users->seed('999900000000000921', Role::SuperAdmin);
        $this->panitia = $users->seed('999900000000000922');
        $this->outsider = $users->seed('999900000000000923');
        $candidates->add($this->panitia, 'Panitia');

        $team = $teams->addTeam(WorkTeamCode::Dta);
        $teams->setRegistrationGuideUrl($team, 'https://drive.example.test/panduan-dta');
        $this->training = $this->trainings->insert(TrainingBuilder::draft(teamId: $team), 1);

        $actors = new TrainingActorProvider($users, $teams, $assignments);
        (new UpdateTrainingAssignments($this->trainings, $assignments, $candidates, $actors, $this->audit, new ImmediateTransaction))
            ->handle($superAdmin, $this->training, [], [$this->panitia], null);

        $values = new TrainingMessageValues($teams, new GlobalSettings($settings), $this->trainings);
        $this->messages = new TrainingMessages($this->trainings, $this->templates, $actors, $values);
        $this->save = new SaveCustomTemplate($this->messages, $this->templates, $this->audit, new ImmediateTransaction);
        $this->reset = new ResetCustomTemplate($this->messages, $this->templates, $this->audit, new ImmediateTransaction);
        $this->audit->events = [];
    }

    public function test_default_template_is_built_from_current_training_data(): void
    {
        $data = $this->messages->for($this->panitia, $this->training);

        $this->assertSame(DefaultTemplate::body(false), $data['default']['body']);
        $text = $data['default']['text'];
        $this->assertStringContainsString('peserta pelatihan Pelatihan Uji Balai', $text);
        $this->assertStringContainsString('https://digitalent.example.test/login', $text);
        $this->assertStringContainsString('Jadwal pendaftaran: 1–10 Desember 2026', $text);
        $this->assertStringContainsString('Jadwal pelatihan: 15–17 Desember 2026', $text);
        $this->assertStringContainsString('Daftar peserta: (menyusul)', $text);
        $this->assertStringContainsString('di sini: (menyusul)', $text);
        $this->assertStringContainsString('https://drive.example.test/panduan-dta', $text);
        $this->assertStringContainsString('alias gratis', $text);
        $this->assertSame([], $data['custom']);
    }

    public function test_learning_path_is_listed_with_links_and_dates(): void
    {
        $basic = $this->trainings->trainings[$this->training];
        $this->trainings->trainings[$this->training] = $basic->revise($basic->teamId, $basic->details, 5, new \DateTimeImmutable('2026-11-04'));
        $this->trainings->insert(TrainingBuilder::draft(teamId: $basic->teamId, details: TrainingBuilder::completeDetails(
            schedule: TrainingBuilder::schedule('2026-12-18', '2026-12-20', '2026-12-21', '2026-12-23'),
            level: TrainingLevel::Menengah,
            name: 'Lanjutan',
        ), pathId: 5), 1);

        $text = $this->messages->for($this->panitia, $this->training)['default']['text'];

        $this->assertStringContainsString('- Level 1 · Dasar: Pelatihan Uji (pelatihan ini)', $text);
        $this->assertStringContainsString("- Level 2 · Menengah: Lanjutan\n  Link: https://digitalent.example.test/daftar\n  Pendaftaran: 18–20 Desember 2026 · Pelatihan: 21–23 Desember 2026", $text);
    }

    public function test_only_training_crew_may_use_templates(): void
    {
        try {
            $this->messages->for($this->outsider, $this->training);
            $this->fail('Pegawai di luar pelatihan seharusnya ditolak.');
        } catch (TrainingAccessDenied) {
        }

        try {
            $this->save->handle($this->outsider, $this->training, null, TemplateBodyTest::VALID);
            $this->fail('Pegawai di luar pelatihan seharusnya ditolak.');
        } catch (TrainingAccessDenied) {
        }

        $this->expectException(TrainingNotFound::class);
        $this->messages->for($this->panitia, 99);
    }

    public function test_save_limit_and_dedup(): void
    {
        $first = $this->save->handle($this->panitia, $this->training, null, TemplateBodyTest::VALID);
        $this->assertNotNull($first);
        $this->assertSame(AuditEventType::MessageTemplateSaved, $this->audit->events[0]->type);
        $this->assertFalse($this->audit->events[0]->context['merged']);

        // Identik dengan template lain atau dengan default: digabung (tidak tersimpan).
        $this->assertNull($this->save->handle($this->panitia, $this->training, null, TemplateBodyTest::VALID."\n"));
        $this->assertNull($this->save->handle($this->panitia, $this->training, null, DefaultTemplate::body(false)));
        $this->assertCount(1, $this->templates->templates);

        $this->save->handle($this->panitia, $this->training, null, TemplateBodyTest::VALID.' 2');
        $this->save->handle($this->panitia, $this->training, null, TemplateBodyTest::VALID.' 3');

        try {
            $this->save->handle($this->panitia, $this->training, null, TemplateBodyTest::VALID.' 4');
            $this->fail('Template keempat seharusnya ditolak.');
        } catch (InvalidMessageTemplate $exception) {
            $this->assertStringContainsString('Maksimal 3', $exception->getMessage());
        }

        // Mengubah template menjadi sama dengan template lain juga menggabungkannya.
        $ids = array_keys($this->templates->templates);
        $this->assertNull($this->save->handle($this->panitia, $this->training, $ids[2], TemplateBodyTest::VALID));
        $this->assertCount(2, $this->templates->templates);
        $this->assertSame(2, count($this->messages->for($this->panitia, $this->training)['custom']));
    }

    public function test_reset_returns_to_single_default_template(): void
    {
        $first = (int) $this->save->handle($this->panitia, $this->training, null, TemplateBodyTest::VALID);
        $second = (int) $this->save->handle($this->panitia, $this->training, null, TemplateBodyTest::VALID.' lagi');

        $this->reset->handle($this->panitia, $this->training, $first);
        $this->reset->handle($this->panitia, $this->training, $second);

        $this->assertSame([], $this->templates->templates);
        $this->assertSame(AuditEventType::MessageTemplateReset, $this->audit->events[2]->type);
        $this->assertSame(1, $this->audit->events[2]->context['removed_count']);
    }

    public function test_cannot_touch_templates_of_others(): void
    {
        $id = (int) $this->save->handle($this->panitia, $this->training, null, TemplateBodyTest::VALID);
        try {
            $this->save->handle($this->panitia, $this->training, $id + 100, TemplateBodyTest::VALID.' x');
            $this->fail('Template milik orang lain atau tidak ada seharusnya ditolak.');
        } catch (TrainingAccessDenied) {
        }

        $this->expectException(TrainingAccessDenied::class);
        $this->reset->handle($this->panitia, $this->training, $id + 100);
    }
}
