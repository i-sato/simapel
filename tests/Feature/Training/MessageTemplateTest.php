<?php

namespace Tests\Feature\Training;

use App\Application\Shared\Contracts\Clock;
use App\Domain\Assignment\AssignmentRole;
use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\TeamRole;
use App\Domain\Training\TrainingStatus;
use App\Models\AuditLog;
use App\Models\MessageTemplateRecord;
use App\Models\TrainingAssignmentRecord;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Models\WorkTeam;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FixedClock;
use Tests\TestCase;
use Tests\Unit\Domain\Messaging\TemplateBodyTest;

final class MessageTemplateTest extends TestCase
{
    use RefreshDatabase;

    private TrainingRecord $training;

    private User $panitia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(Clock::class, new FixedClock(new DateTimeImmutable('2026-11-04 09:00:00')));

        $team = WorkTeam::query()->where('code', 'DTA')->where('year', 2026)->sole();
        $team->forceFill(['registration_guide_url' => 'https://drive.example.test/panduan-dta'])->save();

        $this->training = TrainingRecord::query()->create([
            'work_team_id' => $team->id,
            'status' => TrainingStatus::Draft,
            'name' => 'Analisis Data 📊',
            'registration_url' => 'https://digitalent.example.test/pelatihan/1',
            'registration_start' => '2026-12-01',
            'registration_end' => '2026-12-10',
            'training_start' => '2026-12-15',
            'training_end' => '2026-12-17',
        ]);

        $this->panitia = User::factory()->create();
        TrainingAssignmentRecord::query()->create(['training_id' => $this->training->id, 'user_id' => $this->panitia->id, 'role' => AssignmentRole::Panitia]);
    }

    private function url(string $suffix = ''): string
    {
        return "/trainings/{$this->training->id}/messages{$suffix}";
    }

    public function test_access_for_panitia_ketua_assigned_admin_and_super_admin_only(): void
    {
        $ketua = User::factory()->create();
        WorkTeam::query()->whereKey($this->training->work_team_id)->sole()->memberships()->create(['user_id' => $ketua->id, 'role' => TeamRole::Ketua]);
        $admin = User::factory()->create(['can_be_admin' => true]);
        TrainingAssignmentRecord::query()->create(['training_id' => $this->training->id, 'user_id' => $admin->id, 'role' => AssignmentRole::Admin]);

        foreach ([$this->panitia, $ketua, $admin, User::factory()->superAdmin()->create()] as $user) {
            $this->actingAs($user)->get($this->url())->assertOk()->assertSee('Template WA');
            $this->get("/trainings/{$this->training->id}")->assertSee('Template WA');
        }

        $ketuaLain = User::factory()->create();
        WorkTeam::query()->where('code', 'KDD')->where('year', 2026)->sole()->memberships()->create(['user_id' => $ketuaLain->id, 'role' => TeamRole::Ketua]);

        foreach ([User::factory()->create(), $ketuaLain] as $user) {
            $this->actingAs($user)->get($this->url())->assertForbidden();
            $this->post($this->url(), ['body' => TemplateBodyTest::VALID])->assertForbidden();
            $this->get("/trainings/{$this->training->id}")->assertOk()->assertDontSee('Template WA');
        }

        $this->assertSame(0, MessageTemplateRecord::query()->count());
    }

    public function test_preview_copy_and_whatsapp_link(): void
    {
        $response = $this->actingAs($this->panitia)->get($this->url())
            ->assertOk()
            ->assertSee('Analisis Data 📊')
            ->assertSee('Jadwal pendaftaran: 1–10 Desember 2026')
            ->assertSee('Daftar peserta: (menyusul)')
            ->assertSee('data-copy-target="wa-text"', false)
            ->assertSee('Template saya (0/3)');

        $this->assertStringContainsString('href="https://wa.me/?text=Selamat%20datang', (string) $response->getContent());
    }

    public function test_custom_template_with_emoji_is_saved_rendered_and_reset(): void
    {
        $this->actingAs($this->panitia)->from($this->url('?ubah=baru'));

        $this->post($this->url(), ['body' => TemplateBodyTest::VALID.' 🚀'])->assertRedirect()->assertSessionHas('success');
        $record = MessageTemplateRecord::query()->sole();
        $this->assertStringEndsWith('GRATIS. 🚀', $record->body);
        $this->assertStringContainsString('🎉', $record->body);

        $this->get($this->url('?pilih='.$record->id))
            ->assertSee('Halo peserta Analisis Data 📊 🎉')
            ->assertSee('Template saya (1/3)');

        $this->post($this->url(), ['body' => TemplateBodyTest::VALID.' 🚀'])->assertSessionHas('warning');
        $this->assertSame(1, MessageTemplateRecord::query()->count());

        $this->post($this->url("/{$record->id}/reset"))->assertRedirect($this->url());
        $this->assertSame(0, MessageTemplateRecord::query()->count());

        $types = AuditLog::query()->where('subject_type', 'training')->pluck('event_type')->map(fn ($type) => $type->value)->all();
        $this->assertSame([AuditEventType::MessageTemplateSaved->value, AuditEventType::MessageTemplateSaved->value, AuditEventType::MessageTemplateReset->value], $types);
    }

    public function test_injection_and_missing_elements_are_rejected_and_code_is_never_executed(): void
    {
        $this->actingAs($this->panitia)->from($this->url('?ubah=baru'));

        $this->post($this->url(), ['body' => TemplateBodyTest::VALID.' {{ $training->name }}'])->assertSessionHasErrors('body');
        $this->post($this->url(), ['body' => str_replace('GRATIS', 'murah', TemplateBodyTest::VALID)])->assertSessionHasErrors('body');
        $this->assertSame(0, MessageTemplateRecord::query()->count());

        // Teks berbentuk direktif Blade/PHP boleh disimpan karena hanya diperlakukan sebagai teks.
        $this->post($this->url(), ['body' => "@php echo 'DIEKSEKUSI'; @endphp <?php echo 'PHP'; ?>\n".TemplateBodyTest::VALID]);
        $id = (int) MessageTemplateRecord::query()->value('id');

        $this->get($this->url('?pilih='.$id))
            ->assertSee("@php echo 'DIEKSEKUSI'; @endphp")
            ->assertDontSee('<?php echo', false)
            ->assertSee('&lt;?php echo', false);
    }

    public function test_limit_and_cannot_edit_someone_elses_template(): void
    {
        $this->actingAs($this->panitia);

        foreach ([1, 2, 3] as $n) {
            $this->post($this->url(), ['body' => TemplateBodyTest::VALID." {$n}"])->assertSessionHasNoErrors();
        }

        $this->from($this->url('?ubah=baru'))->post($this->url(), ['body' => TemplateBodyTest::VALID.' 4'])->assertSessionHasErrors('body');
        $this->get($this->url('?ubah=baru'))->assertSee('Template saya (3/3)')->assertDontSee('＋ Template baru');

        $other = User::factory()->superAdmin()->create();
        $id = (int) MessageTemplateRecord::query()->value('id');
        $this->actingAs($other)->put($this->url("/{$id}"), ['body' => TemplateBodyTest::VALID.' diambil'])->assertForbidden();
        $this->post($this->url("/{$id}/reset"))->assertForbidden();
        $this->assertSame(3, MessageTemplateRecord::query()->count());
    }
}
