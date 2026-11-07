<?php

namespace Tests\Feature\Admin;

use App\Application\Shared\Contracts\Clock;
use App\Domain\Audit\AuditEventType;
use App\Domain\Sop\SopProvisionStatus;
use App\Models\AuditLog;
use App\Models\SopProvisionRecord;
use App\Models\User;
use Database\Seeders\SopProvisionDraftSeeder;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FixedClock;
use Tests\TestCase;

final class SopProvisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(Clock::class, new FixedClock(new DateTimeImmutable('2026-11-04 09:00:00')));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'team' => 'DTA',
            'fiscal_year' => 2026,
            'sop_number' => 'SOP/DTA/2026',
            'sop_effective_date' => '2026-01-02',
            'sop_url' => 'https://drive.example.test/sop-dta',
            'effective_from' => '2026-01-02',
            'bap_days' => 1,
            'sk_days' => 2,
            'st_days' => 2,
            'ready_days' => 3,
            'note' => 'Catatan uji',
        ], $overrides);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->from('/settings');

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function draft(array $overrides = []): SopProvisionRecord
    {
        $this->post('/settings/sop-provisions', $this->form($overrides))->assertSessionHasNoErrors();

        return SopProvisionRecord::query()->latest('id')->firstOrFail();
    }

    public function test_pegawai_cannot_manage_provisions(): void
    {
        $this->actingAs(User::factory()->create());
        $provision = SopProvisionRecord::query()->create(array_merge(
            array_diff_key($this->form(), ['team' => true]),
            ['team_code' => 'DTA', 'version' => 1, 'status' => SopProvisionStatus::Draf],
        ));

        $this->get('/settings/sop-provisions/create?team=DTA&year=2026')->assertForbidden();
        $this->post('/settings/sop-provisions', $this->form())->assertForbidden();
        $this->post('/settings/sop-provisions/copy', ['team' => 'DTA', 'year' => 2027])->assertForbidden();
        $this->get("/settings/sop-provisions/{$provision->id}/edit")->assertForbidden();
        $this->put("/settings/sop-provisions/{$provision->id}", $this->form())->assertForbidden();
        $this->post("/settings/sop-provisions/{$provision->id}/activate")->assertForbidden();
        $this->delete("/settings/sop-provisions/{$provision->id}")->assertForbidden();

        $this->assertSame(SopProvisionStatus::Draf, $provision->refresh()->status);
        $this->assertDatabaseCount('sop_provisions', 1);
    }

    public function test_super_admin_creates_edits_and_deletes_draft(): void
    {
        $this->superAdmin();
        $this->get('/settings/sop-provisions/create?team=DTA&year=2026')->assertOk()->assertSee('Talenta Digital');

        $provision = $this->draft();
        $this->assertSame(1, $provision->version);
        $this->assertSame(SopProvisionStatus::Draf, $provision->status);

        $this->get("/settings/sop-provisions/{$provision->id}/edit")->assertOk()->assertSee('SOP/DTA/2026');
        $this->put("/settings/sop-provisions/{$provision->id}", $this->form(['ready_days' => 4]))->assertSessionHasNoErrors();
        $this->assertSame(4, $provision->refresh()->ready_days);

        $this->delete("/settings/sop-provisions/{$provision->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseCount('sop_provisions', 0);

        $types = AuditLog::query()->where('subject_type', 'sop_provision')->orderBy('id')->pluck('event_type')->map(fn ($type) => $type->value)->all();
        $this->assertSame([
            AuditEventType::SopProvisionDrafted->value,
            AuditEventType::SopProvisionUpdated->value,
            AuditEventType::SopProvisionDeleted->value,
        ], $types);
    }

    public function test_server_validates_deadlines_and_links(): void
    {
        $this->superAdmin();

        $this->post('/settings/sop-provisions', $this->form(['ready_days' => 1]))->assertSessionHasErrors('ready_days');
        $this->post('/settings/sop-provisions', $this->form(['bap_days' => 61]))->assertSessionHasErrors('bap_days');
        $this->post('/settings/sop-provisions', $this->form(['sop_url' => 'javascript:alert(1)']))->assertSessionHasErrors('sop_url');
        $this->post('/settings/sop-provisions', $this->form(['effective_from' => '2027-01-01']))->assertSessionHasErrors('effective_from');
        $this->post('/settings/sop-provisions', $this->form(['team' => 'XYZ']))->assertSessionHasErrors('team');

        $this->assertDatabaseCount('sop_provisions', 0);
    }

    public function test_activation_locks_version_and_mid_year_revision_supersedes_it(): void
    {
        $this->superAdmin();
        $v1 = $this->draft();
        $this->post("/settings/sop-provisions/{$v1->id}/activate")->assertSessionHasNoErrors();
        $this->assertSame(SopProvisionStatus::Berlaku, $v1->refresh()->status);

        $this->get("/settings/sop-provisions/{$v1->id}/edit")->assertRedirect()->assertSessionHasErrors('general');
        $this->put("/settings/sop-provisions/{$v1->id}", $this->form(['ready_days' => 9]))->assertSessionHasErrors();
        $this->delete("/settings/sop-provisions/{$v1->id}")->assertSessionHasErrors();
        $this->assertSame(3, $v1->refresh()->ready_days);

        $v2 = $this->draft(['effective_from' => '2026-07-01', 'ready_days' => 5]);
        $this->assertSame(2, $v2->version);
        $this->post("/settings/sop-provisions/{$v2->id}/activate")->assertSessionHasNoErrors();

        $this->assertSame(SopProvisionStatus::Diganti, $v1->refresh()->status);
        $this->assertSame('2026-07-01', $v1->superseded_from?->format('Y-m-d'));
        $this->assertSame(SopProvisionStatus::Berlaku, $v2->refresh()->status);
        $this->assertSame(1, AuditLog::query()->where('event_type', AuditEventType::SopProvisionSuperseded)->count());

        $this->get('/settings?year=2026')->assertOk()->assertSee('Berlaku hari ini: versi 2 (2026)')->assertSee('Diganti');
    }

    public function test_activation_requires_sop_link(): void
    {
        $this->superAdmin();
        $draft = $this->draft(['sop_url' => '']);

        $this->post("/settings/sop-provisions/{$draft->id}/activate")->assertSessionHasErrors('sop_url');

        $this->assertSame(SopProvisionStatus::Draf, $draft->refresh()->status);
    }

    public function test_copy_from_previous_year_creates_draft(): void
    {
        $this->superAdmin();
        $source = $this->draft();
        $this->post("/settings/sop-provisions/{$source->id}/activate");

        $this->post('/settings/sop-provisions/copy', ['team' => 'DTA', 'year' => 2027])->assertRedirect('/settings?year=2027');

        $copy = SopProvisionRecord::query()->where('fiscal_year', 2027)->sole();
        $this->assertSame(SopProvisionStatus::Draf, $copy->status);
        $this->assertSame('2027-01-01', $copy->effective_from->format('Y-m-d'));
        $this->assertSame('SOP/DTA/2026', $copy->sop_number);

        $this->post('/settings/sop-provisions/copy', ['team' => 'ASN', 'year' => 2027])->assertSessionHasErrors();
    }

    public function test_seeder_creates_idempotent_drafts_without_activating(): void
    {
        $this->seed(SopProvisionDraftSeeder::class);
        $this->seed(SopProvisionDraftSeeder::class);

        $this->assertSame(3, SopProvisionRecord::query()->count());
        $this->assertSame(0, SopProvisionRecord::query()->where('status', '!=', SopProvisionStatus::Draf)->count());
        $this->assertNull(SopProvisionRecord::query()->whereNotNull('sop_url')->first());
        $kdd = SopProvisionRecord::query()->where('team_code', 'KDD')->sole();
        $this->assertSame([5, 6, 6, 9], [$kdd->bap_days, $kdd->sk_days, $kdd->st_days, $kdd->ready_days]);
        $this->assertStringContainsString('Perlu konfirmasi', (string) $kdd->note);
    }
}
