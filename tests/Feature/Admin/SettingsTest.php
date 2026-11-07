<?php

namespace Tests\Feature\Admin;

use App\Application\Shared\Contracts\Clock;
use App\Domain\Audit\AuditEventType;
use App\Domain\Sop\SopProvisionStatus;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\SopProvisionRecord;
use App\Models\User;
use App\Models\WorkTeam;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FixedClock;
use Tests\TestCase;

final class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(Clock::class, new FixedClock(new DateTimeImmutable('2026-11-04 09:00:00')));
    }

    private function team(string $code = 'KDD'): WorkTeam
    {
        return WorkTeam::query()->where('code', $code)->where('year', 2026)->sole();
    }

    public function test_pegawai_gets_403_on_settings_page_and_actions(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/settings')->assertForbidden();
        $this->put('/settings/global', ['closing_soon_days' => '3'])->assertForbidden();
        $this->put('/settings/teams/'.$this->team()->id.'/guide', ['registration_guide_url' => 'https://example.test'])->assertForbidden();

        $this->assertDatabaseCount('settings', 0);
        $this->assertNull($this->team()->registration_guide_url);
    }

    public function test_menu_item_is_only_shown_to_super_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertDontSee('href="'.route('settings.index').'"', false);
        $this->actingAs(User::factory()->superAdmin()->create())->get('/dashboard')->assertSee('href="'.route('settings.index').'"', false);
    }

    public function test_super_admin_sees_defaults_and_missing_sop_badges(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/settings')
            ->assertOk()
            ->assertSee('Ambang')
            ->assertSee('value="7"', false)
            ->assertSee('SOP belum dilampirkan')
            ->assertSee('Ketentuan SOP tahun anggaran 2026');
    }

    public function test_super_admin_updates_global_settings_with_audit(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())->from('/settings');

        $this->put('/settings/global', [
            'digitalent_login_url' => 'https://digitalent.komdigi.go.id/login',
            'digitalent_register_url' => '',
            'dts_registration_guide_url' => '',
            'closing_soon_days' => '10',
        ])->assertRedirect('/settings')->assertSessionHasNoErrors();

        $this->assertSame('10', Setting::query()->where('key', 'closing_soon_days')->value('value'));
        $log = AuditLog::query()->where('event_type', AuditEventType::SettingUpdated)->where('context->setting', 'closing_soon_days')->sole();
        $this->assertSame('7', $log->context['old_value'] ?? null);
        $this->assertSame('10', $log->context['new_value'] ?? null);
        $this->assertSame(2, AuditLog::query()->where('event_type', AuditEventType::SettingUpdated)->count());
    }

    public function test_unsafe_url_and_out_of_range_threshold_are_rejected(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())->from('/settings');

        $this->put('/settings/global', ['digitalent_login_url' => 'javascript:alert(1)', 'closing_soon_days' => '7'])
            ->assertSessionHasErrors('digitalent_login_url');
        $this->put('/settings/global', ['closing_soon_days' => '61'])->assertSessionHasErrors('closing_soon_days');

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_super_admin_sets_team_guide_shown_on_work_team_page(): void
    {
        $team = $this->team('ASN');
        $this->actingAs(User::factory()->superAdmin()->create())->from('/settings');

        $this->put("/settings/teams/{$team->id}/guide", ['registration_guide_url' => 'https://drive.example.test/panduan-asn'])
            ->assertSessionHasNoErrors();
        $this->put("/settings/teams/{$team->id}/guide", ['registration_guide_url' => 'ftp://example.test'])
            ->assertSessionHasErrors('registration_guide_url');

        $this->assertSame('https://drive.example.test/panduan-asn', $team->refresh()->registration_guide_url);
        $this->assertSame(1, AuditLog::query()->where('event_type', AuditEventType::TeamGuideUpdated)->count());

        $this->actingAs(User::factory()->create())
            ->get("/work-teams/{$team->id}")
            ->assertSee('https://drive.example.test/panduan-asn');
    }

    public function test_work_team_pages_show_sop_link_or_badges(): void
    {
        SopProvisionRecord::query()->create($this->provision('DTA', 2026, SopProvisionStatus::Berlaku, 'https://drive.example.test/sop-dta'));
        SopProvisionRecord::query()->create($this->provision('KDD', 2025, SopProvisionStatus::Berlaku, 'https://drive.example.test/sop-kdd-2025'));

        $this->actingAs(User::factory()->create())
            ->get('/work-teams')
            ->assertSee('https://drive.example.test/sop-dta')
            ->assertSee('SOP belum dilampirkan')
            ->assertDontSee('Ketentuan 2026 belum ditetapkan');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/work-teams')
            ->assertSee('Ketentuan 2026 belum ditetapkan');
    }

    /**
     * @return array<string, mixed>
     */
    private function provision(string $team, int $year, SopProvisionStatus $status, ?string $url): array
    {
        return [
            'team_code' => $team,
            'fiscal_year' => $year,
            'version' => 1,
            'status' => $status,
            'sop_number' => "SOP/{$team}/{$year}",
            'sop_effective_date' => "{$year}-01-01",
            'sop_url' => $url,
            'effective_from' => "{$year}-01-01",
            'bap_days' => 1,
            'sk_days' => 2,
            'st_days' => 2,
            'ready_days' => 3,
        ];
    }
}
