<?php

namespace Tests\Unit\Application\Settings;

use App\Application\Organization\Exceptions\WorkTeamNotFound;
use App\Application\Organization\UpdateTeamGuideUrl;
use App\Application\Settings\GlobalSettings;
use App\Application\Settings\UpdateGlobalSettings;
use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Settings\GlobalSetting;
use App\Domain\Settings\InvalidSetting;
use App\Domain\Shared\InvalidUrl;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuditTrail;
use Tests\Support\Fakes\InMemorySettingsRepository;
use Tests\Support\Fakes\InMemoryWorkTeamRepository;

final class GlobalSettingsUseCasesTest extends TestCase
{
    private InMemorySettingsRepository $repository;

    private InMemoryAuditTrail $audit;

    private UpdateGlobalSettings $update;

    protected function setUp(): void
    {
        $this->repository = new InMemorySettingsRepository;
        $this->audit = new InMemoryAuditTrail;
        $this->update = new UpdateGlobalSettings($this->repository, new GlobalSettings($this->repository), $this->audit, new ImmediateTransaction);
    }

    public function test_defaults_apply_when_nothing_stored(): void
    {
        $settings = new GlobalSettings($this->repository);

        $this->assertSame(7, $settings->closingSoonDays());
        $this->assertNull($settings->get(GlobalSetting::DigitalentLoginUrl));
        $this->assertCount(count(GlobalSetting::cases()), $settings->all());
    }

    public function test_only_changed_settings_are_saved_and_audited(): void
    {
        $changed = $this->update->handle([
            'digitalent_login_url' => 'https://digitalent.komdigi.go.id/login',
            'closing_soon_days' => '7',
            'unknown_key' => 'abaikan',
        ], actorId: 1);

        $this->assertSame(1, $changed);
        $this->assertSame(['digitalent_login_url' => 'https://digitalent.komdigi.go.id/login'], $this->repository->values);
        $event = $this->audit->events[0];
        $this->assertSame(AuditEventType::SettingUpdated, $event->type);
        $this->assertNull($event->context['old_value']);
        $this->assertSame('https://digitalent.komdigi.go.id/login', $event->context['new_value']);

        $this->assertSame(0, $this->update->handle(['digitalent_login_url' => 'https://digitalent.komdigi.go.id/login'], 1));
        $this->assertSame(1, $this->update->handle(['closing_soon_days' => '10'], 1));
        $this->assertSame('7', $this->audit->events[1]->context['old_value']);
    }

    public function test_invalid_value_rejects_whole_update(): void
    {
        try {
            $this->update->handle(['digitalent_login_url' => 'https://ok.example.test', 'closing_soon_days' => '0'], 1);
            $this->fail('Nilai tidak valid seharusnya ditolak.');
        } catch (InvalidSetting) {
        }

        $this->assertSame([], $this->repository->values);
        $this->assertSame([], $this->audit->events);
    }

    public function test_team_guide_url_is_validated_and_audited(): void
    {
        $teams = new InMemoryWorkTeamRepository;
        $teamId = $teams->addTeam(WorkTeamCode::Kdd);
        $useCase = new UpdateTeamGuideUrl($teams, $this->audit, new ImmediateTransaction);

        $useCase->handle($teamId, ' https://drive.example.test/panduan-kdd ', 1);
        $useCase->handle($teamId, 'https://drive.example.test/panduan-kdd', 1);

        $this->assertSame('https://drive.example.test/panduan-kdd', $teams->teams[$teamId]->registrationGuideUrl);
        $this->assertCount(1, $this->audit->events);
        $this->assertSame(AuditEventType::TeamGuideUpdated, $this->audit->events[0]->type);

        $useCase->handle($teamId, '', 1);
        $this->assertNull($teams->teams[$teamId]->registrationGuideUrl);

        try {
            $useCase->handle($teamId, 'javascript:alert(1)', 1);
            $this->fail('URL tidak aman seharusnya ditolak.');
        } catch (InvalidUrl) {
        }

        $this->expectException(WorkTeamNotFound::class);
        $useCase->handle(99, null, 1);
    }
}
