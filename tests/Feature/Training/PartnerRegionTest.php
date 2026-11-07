<?php

namespace Tests\Feature\Training;

use App\Application\Shared\Contracts\Clock;
use App\Domain\Assignment\AssignmentRole;
use App\Domain\Organization\TeamRole;
use App\Domain\Shared\WorkRegion;
use App\Domain\Training\TrainingStatus;
use App\Models\PartnerRecord;
use App\Models\TrainingAssignmentRecord;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Models\WorkTeam;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FixedClock;
use Tests\TestCase;

final class PartnerRegionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(Clock::class, new FixedClock(new DateTimeImmutable('2026-11-04 09:00:00')));
    }

    private function team(string $code = 'DTA'): WorkTeam
    {
        return WorkTeam::query()->where('code', $code)->where('year', 2026)->sole();
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string|int>
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'work_team_id' => $this->team()->id,
            'name' => 'Pelatihan Wilayah',
            'registration_start' => '2026-12-01',
            'registration_end' => '2026-12-10',
            'training_start' => '2026-12-15',
            'training_end' => '2026-12-17',
            'execution' => 'mitra',
        ], $overrides);
    }

    public function test_everyone_sees_partner_list_but_only_authorized_manage(): void
    {
        PartnerRecord::query()->create(['name' => 'PT Maju Jaya', 'normalized_name' => 'pt maju jaya', 'region' => WorkRegion::Bali]);

        $this->actingAs(User::factory()->create())->get('/partners')
            ->assertOk()->assertSee('PT Maju Jaya')->assertSee('Bali')->assertDontSee('Tambah mitra');
        $this->post('/partners', ['partner_name' => 'Mitra Baru', 'work_region' => 'diy'])->assertForbidden();

        $admin = User::factory()->create(['can_be_admin' => true]);
        $this->actingAs($admin)->get('/partners')->assertSee('Tambah mitra');
        $this->post('/partners', ['partner_name' => 'Mitra Baru', 'work_region' => 'diy'])->assertSessionHasNoErrors();
        $this->from('/partners')->post('/partners', ['partner_name' => 'mitra   BARU', 'work_region' => 'bali'])->assertSessionHasErrors('partner_name');

        $ketua = User::factory()->create();
        $this->team('KDD')->memberships()->create(['user_id' => $ketua->id, 'role' => TeamRole::Ketua]);
        $partner = PartnerRecord::query()->where('name', 'Mitra Baru')->sole();
        $this->actingAs($ketua)->put("/partners/{$partner->id}", ['partner_name' => 'Mitra Baru Jateng', 'work_region' => 'jawa_tengah'])->assertSessionHasNoErrors();

        $this->assertSame(WorkRegion::JawaTengah, $partner->refresh()->region);
        $this->assertSame(2, PartnerRecord::query()->count());
    }

    public function test_training_partner_is_picked_or_registered_and_region_follows_partner(): void
    {
        $ketua = User::factory()->create();
        $this->team()->memberships()->create(['user_id' => $ketua->id, 'role' => TeamRole::Ketua]);
        PartnerRecord::query()->create(['name' => 'PT Maju Jaya', 'normalized_name' => 'pt maju jaya', 'region' => WorkRegion::Bali]);
        $this->actingAs($ketua)->from('/trainings/create');

        $this->get('/trainings/create')->assertSee('<option value="PT Maju Jaya">Bali</option>', false)->assertSee('value="23:59"', false);

        $this->post('/trainings', $this->form(['partner_name' => 'pt maju jaya', 'work_region' => 'diy']))->assertSessionHasNoErrors();
        $existing = TrainingRecord::query()->latest('id')->firstOrFail();
        $this->assertSame(WorkRegion::Bali, $existing->region);
        $this->assertSame('PT Maju Jaya', $existing->partner?->name);
        $this->assertSame('23:59:00', $existing->registration_closes_at);

        $this->post('/trainings', $this->form(['partner_name' => 'Universitas Uji', 'work_region' => '']))->assertSessionHasErrors('work_region');
        $this->post('/trainings', $this->form(['partner_name' => 'Universitas Uji', 'work_region' => 'jawa_tengah', 'registration_closes_at' => '16:00']))->assertSessionHasNoErrors();
        $created = TrainingRecord::query()->latest('id')->firstOrFail();
        $this->assertSame(WorkRegion::JawaTengah, $created->partner?->region);
        $this->assertSame('16:00:00', $created->registration_closes_at);

        $this->post('/trainings', $this->form(['registration_closes_at' => '25:00']))->assertSessionHasErrors('registration_closes_at');

        $this->get("/trainings/{$existing->id}")
            ->assertSee('Bali (WITA)')
            ->assertSee('11 Desember 2026 pukul 00.59 WITA (10 Desember 2026 pukul 23.59 WIB)');
    }

    public function test_assigned_admin_can_register_partner_from_training_form(): void
    {
        $training = TrainingRecord::query()->create(['work_team_id' => $this->team()->id, 'status' => TrainingStatus::Draft, 'name' => 'Draft']);
        $admin = User::factory()->create(['can_be_admin' => true]);
        TrainingAssignmentRecord::query()->create(['training_id' => $training->id, 'user_id' => $admin->id, 'role' => AssignmentRole::Admin]);

        // Admin yang ditugaskan (bertanda Admin) boleh mendaftarkan mitra baru dari form.
        $this->actingAs($admin)->from("/trainings/{$training->id}/edit")
            ->put("/trainings/{$training->id}", $this->form(['partner_name' => 'Mitra Admin', 'work_region' => 'bali']))
            ->assertSessionHasNoErrors();
        $this->assertSame(WorkRegion::Bali, $training->refresh()->region);
    }

    public function test_wa_template_shows_closing_time_in_region_timezone_and_layout_says_wilayah_kerja(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $training = TrainingRecord::query()->create([
            'work_team_id' => $this->team()->id, 'status' => TrainingStatus::Draft, 'name' => 'Pelatihan Bali',
            'registration_start' => '2026-12-01', 'registration_end' => '2026-12-10', 'training_start' => '2026-12-15', 'training_end' => '2026-12-17',
            'region' => WorkRegion::Bali,
        ]);

        $this->actingAs($admin)->get("/trainings/{$training->id}/messages")
            ->assertSee('Pendaftaran ditutup: 11 Desember 2026 pukul 00.59 WITA (10 Desember 2026 pukul 23.59 WIB)')
            ->assertSee('Wilayah kerja: DIY, Jawa Tengah, dan Bali')
            ->assertDontSee('Wilayah layanan');

        $training->forceFill(['region' => WorkRegion::Diy, 'registration_closes_at' => '20:00:00'])->save();
        $this->get("/trainings/{$training->id}/messages")->assertSee('Pendaftaran ditutup: 10 Desember 2026 pukul 20.00 WIB');
    }
}
