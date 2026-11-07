<?php

namespace Tests\Feature\Training;

use App\Application\Shared\Contracts\Clock;
use App\Application\Sop\Contracts\SopProvisionRepository;
use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\TeamRole;
use App\Domain\Sop\SopProvisionStatus;
use App\Domain\Training\TrainingStatus;
use App\Models\AuditLog;
use App\Models\LearningPathRecord;
use App\Models\SopProvisionRecord;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Models\WorkTeam;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FixedClock;
use Tests\TestCase;

final class TrainingTest extends TestCase
{
    use RefreshDatabase;

    private function useClock(string $now): void
    {
        $this->app->instance(Clock::class, new FixedClock(new DateTimeImmutable($now)));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->useClock('2026-11-04 09:00:00');
    }

    private function team(string $code): WorkTeam
    {
        return WorkTeam::query()->where('code', $code)->where('year', 2026)->sole();
    }

    private function ketuaOf(string $code): User
    {
        $user = User::factory()->create();
        $this->team($code)->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Ketua]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(string $team = 'DTA', array $overrides = []): array
    {
        return array_merge([
            'work_team_id' => $this->team($team)->id,
            'name' => 'Pelatihan Analisis Data',
            'registration_url' => 'https://digitalent.example.test/daftar',
            'registration_start' => '2026-12-01',
            'registration_end' => '2026-12-10',
            'training_start' => '2026-12-15',
            'training_end' => '2026-12-17',
            'partner_location' => 'Mitra Uji, Yogyakarta',
            'participant_list_url' => '',
            'method' => 'daring',
            'batch' => '1',
            'syllabus_url' => 'https://drive.example.test/silabus',
            'level' => 'dasar',
            'certification' => 'industri',
            'coc_shows_score' => '1',
            'execution' => 'swakelola',
            'participant_category' => 'umum',
            'zonation' => 'wilayah_kerja',
            'lesson_hours' => '32',
            'participant_target' => '100',
            'registration_quota' => '300',
            'learning_path_id' => '',
            'new_learning_path' => '',
            'work_region' => 'diy',
            'registration_closes_at' => '23:59',
            'partner_name' => '',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $form
     */
    private function storeAs(User $user, array $form): TrainingRecord
    {
        $this->actingAs($user)->from('/trainings/create')->post('/trainings', $form)->assertSessionHasNoErrors()->assertRedirect();

        return TrainingRecord::query()->latest('id')->firstOrFail();
    }

    private function activateProvision(string $team, int $year, int $readyDays = 3): SopProvisionRecord
    {
        return SopProvisionRecord::query()->create([
            'team_code' => $team, 'fiscal_year' => $year, 'version' => 1, 'status' => SopProvisionStatus::Berlaku,
            'sop_number' => "SOP/{$team}/{$year}", 'sop_effective_date' => "{$year}-01-01", 'sop_url' => "https://drive.example.test/sop-{$team}",
            'effective_from' => "{$year}-01-01", 'bap_days' => 1, 'sk_days' => 1, 'st_days' => 1, 'ready_days' => $readyDays,
        ]);
    }

    public function test_every_employee_can_view_list_and_detail_but_not_manage(): void
    {
        $training = $this->storeAs(User::factory()->superAdmin()->create(), $this->form());
        $employee = User::factory()->create();

        $this->actingAs($employee)->get('/trainings')
            ->assertOk()
            ->assertSee('Pelatihan Analisis Data')
            ->assertSee('Draft')
            ->assertSee('Akan dibuka')
            ->assertDontSee('＋ Draft pelatihan');

        $this->get("/trainings/{$training->id}")
            ->assertOk()
            ->assertSee('Level 1 · Dasar')
            ->assertSee('SOP belum dilampirkan')
            ->assertDontSee('Finalisasi</button>', false)
            ->assertDontSee('Ubah draft');

        $this->get('/trainings/create')->assertForbidden();
        $this->post('/trainings', $this->form())->assertForbidden();
        $this->get("/trainings/{$training->id}/edit")->assertForbidden();
        $this->put("/trainings/{$training->id}", $this->form())->assertForbidden();
        $this->post("/trainings/{$training->id}/finalize")->assertForbidden();

        $this->assertSame(TrainingStatus::Draft, $training->refresh()->status);
        $this->assertSame(1, TrainingRecord::query()->count());
    }

    public function test_ketua_creates_and_edits_drafts_only_for_own_team(): void
    {
        $ketua = $this->ketuaOf('DTA');

        $this->actingAs($ketua)->get('/trainings/create')
            ->assertOk()
            ->assertSee('Talenta Digital (DTA) 2026')
            ->assertDontSee('Keterampilan Digital Dasar (KDD) 2026')
            ->assertSee('SOP belum dilampirkan');

        $training = $this->storeAs($ketua, $this->form('DTA', ['level' => '', 'batch' => '']));
        $this->assertSame(AuditEventType::TrainingDrafted, AuditLog::query()->where('subject_type', 'training')->sole()->event_type);

        $this->post('/trainings', $this->form('KDD'))->assertForbidden();
        $this->put("/trainings/{$training->id}", $this->form('KDD'))->assertForbidden();

        $this->get("/trainings/{$training->id}/edit")->assertOk();
        $this->put("/trainings/{$training->id}", $this->form('DTA', ['name' => 'Nama diperbarui']))->assertRedirect("/trainings/{$training->id}");
        $this->assertSame('Nama diperbarui', $training->refresh()->name);

        // Ketua tidak memfinalisasi; tombolnya tidak tampil dan aksinya ditolak.
        $this->get("/trainings/{$training->id}")->assertDontSee('Finalisasi</button>', false)->assertSee('Ubah draft');
        $this->post("/trainings/{$training->id}/finalize")->assertForbidden();

        // Ketua tim lain tidak dapat mengubah draft DTA.
        $this->actingAs($this->ketuaOf('KDD'))->get("/trainings/{$training->id}/edit")->assertForbidden();
    }

    public function test_server_validates_input(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())->from('/trainings/create');

        $this->post('/trainings', $this->form('DTA', ['name' => '']))->assertSessionHasErrors('name');
        $this->post('/trainings', $this->form('DTA', ['registration_url' => 'javascript:alert(1)']))->assertSessionHasErrors('registration_url');
        $this->post('/trainings', $this->form('DTA', ['training_start' => '2026-12-05']))->assertSessionHasErrors('training_start');
        $this->post('/trainings', $this->form('DTA', ['lesson_hours' => '0']))->assertSessionHasErrors('lesson_hours');
        $this->post('/trainings', $this->form('DTA', ['method' => 'teleportasi']))->assertSessionHasErrors('method');
        $this->post('/trainings', $this->form('DTA', ['participant_list_url' => 'https://docs.example.test/peserta']))->assertSessionHasErrors('participant_list_url');

        $this->assertSame(0, TrainingRecord::query()->count());
    }

    public function test_super_admin_finalizes_and_snapshot_locks_provision(): void
    {
        $provision = $this->activateProvision('DTA', 2026, readyDays: 9);
        $admin = User::factory()->superAdmin()->create();
        $training = $this->storeAs($admin, $this->form());

        $this->get("/trainings/{$training->id}")->assertSee('Finalisasi</button>', false)->assertSee('lebih pendek dari tenggat administrasi siap H+9');

        $this->post("/trainings/{$training->id}/finalize")
            ->assertRedirect("/trainings/{$training->id}")
            ->assertSessionHas('warning');

        $training->refresh();
        $this->assertSame(TrainingStatus::Final, $training->status);
        $this->assertSame($provision->id, $training->sop_provision_id);
        $this->assertSame(9, $training->sop_ready_days);
        $this->assertSame($admin->id, TrainingRecord::query()->whereKey($training->id)->value('finalized_by'));
        $this->assertTrue($this->app->make(SopProvisionRepository::class)->isReferenced($provision->id));

        $this->get("/trainings/{$training->id}")
            ->assertSee('Ketentuan SOP tersalin saat finalisasi')
            ->assertSee('DTA 2026 versi 1')
            ->assertDontSee('Finalisasi</button>', false);

        // Final tidak dapat difinalisasi ulang.
        $this->post("/trainings/{$training->id}/finalize")->assertForbidden();
        $this->assertSame(1, AuditLog::query()->where('event_type', AuditEventType::TrainingFinalized)->count());
    }

    public function test_incomplete_draft_cannot_be_finalized(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $training = $this->storeAs($admin, $this->form('DTA', ['syllabus_url' => '']));

        $this->get("/trainings/{$training->id}")->assertSee('Belum dapat difinalisasi')->assertSee('link silabus');
        $this->from("/trainings/{$training->id}")->post("/trainings/{$training->id}/finalize")->assertSessionHasErrors('general');

        $this->assertSame(TrainingStatus::Draft, $training->refresh()->status);
    }

    public function test_final_training_is_completed_by_team_ketua_and_super_admin(): void
    {
        $ketua = $this->ketuaOf('DTA');
        $admin = User::factory()->superAdmin()->create();
        $training = $this->storeAs($ketua, $this->form());
        $this->actingAs($admin)->post("/trainings/{$training->id}/finalize");

        // Ketua tim terkait melengkapi pelatihan Final; tombol finalisasi tetap tidak ada.
        $this->actingAs($ketua)->get("/trainings/{$training->id}")->assertSee('Lengkapi')->assertDontSee('Finalisasi</button>', false);
        $this->get("/trainings/{$training->id}/edit")->assertOk()->assertSee('Pelatihan sudah Final');

        // Link daftar peserta: ditolak selama pendaftaran, diterima setelah pendaftaran ditutup.
        $this->from("/trainings/{$training->id}/edit");
        $this->put("/trainings/{$training->id}", $this->form('DTA', ['participant_list_url' => 'https://docs.example.test/peserta']))
            ->assertSessionHasErrors('participant_list_url');

        $this->useClock('2026-12-11 08:00:00');
        $this->put("/trainings/{$training->id}", $this->form('DTA', ['participant_list_url' => 'https://docs.example.test/peserta']))
            ->assertSessionHasNoErrors();
        $this->assertSame('https://docs.example.test/peserta', $training->refresh()->participant_list_url);

        // Data Final harus tetap lengkap; Ketua tidak dapat memindahkan ke tim yang tidak dipimpinnya.
        $this->put("/trainings/{$training->id}", $this->form('DTA', ['lesson_hours' => '']))->assertSessionHasErrors('general');
        $this->put("/trainings/{$training->id}", $this->form('KDD'))->assertForbidden();

        // Super Admin pun tidak dapat mengganti tim pelatihan Final.
        $this->actingAs($admin)->from("/trainings/{$training->id}/edit");
        $this->put("/trainings/{$training->id}", $this->form('KDD'))->assertSessionHasErrors('work_team_id');
        $this->put("/trainings/{$training->id}", $this->form('DTA', ['name' => 'Nama oleh Super Admin', 'participant_list_url' => 'https://docs.example.test/peserta']))
            ->assertSessionHasNoErrors();

        // Ketua tim lain dan pegawai biasa tidak dapat melengkapi.
        $this->actingAs($this->ketuaOf('KDD'))->get("/trainings/{$training->id}/edit")->assertForbidden();
        $this->actingAs(User::factory()->create())->put("/trainings/{$training->id}", $this->form())->assertForbidden();

        $training->refresh();
        $this->assertSame(TrainingStatus::Final, $training->status);
        $this->assertSame('Nama oleh Super Admin', $training->name);
        $this->assertSame(2, AuditLog::query()->where('event_type', AuditEventType::TrainingUpdated)->count());
    }

    public function test_learning_path_neighbours_are_shown(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $basic = $this->storeAs($admin, $this->form('DTA', ['name' => 'VBL Dasar', 'new_learning_path' => 'VBL Data']));
        $path = LearningPathRecord::query()->sole();
        $advanced = $this->storeAs($admin, $this->form('DTA', ['name' => 'VBL Mahir', 'level' => 'mahir', 'learning_path_id' => (string) $path->id]));

        $this->from('/trainings/create')
            ->post('/trainings', $this->form('DTA', ['name' => 'Dobel', 'learning_path_id' => (string) $path->id]))
            ->assertSessionHasErrors('level');

        $this->get("/trainings/{$basic->id}")->assertSee('VBL Data')->assertSee('Level 3 · Mahir: VBL Mahir →');
        $this->get("/trainings/{$advanced->id}")->assertSee('← Level 1 · Dasar: VBL Dasar');
        $this->get('/trainings/create')->assertSee('VBL Data');
    }

    public function test_list_filters_by_team_status_phase_and_period(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $this->storeAs($admin, $this->form('DTA', ['name' => 'Pelatihan Desember']));
        $this->storeAs($admin, $this->form('KDD', [
            'name' => 'Pelatihan November', 'registration_start' => '2026-10-20', 'registration_end' => '2026-11-10',
            'training_start' => '2026-11-20', 'training_end' => '2026-11-21',
        ]));
        $this->storeAs($admin, $this->form('ASN', ['name' => 'Draft Tanpa Jadwal', 'registration_start' => '', 'registration_end' => '', 'training_start' => '', 'training_end' => '']));

        $this->get('/trainings?team=KDD')->assertSee('Pelatihan November')->assertDontSee('Pelatihan Desember');
        $this->get('/trainings?status=pendaftaran')->assertSee('Pelatihan November')->assertDontSee('Pelatihan Desember')->assertDontSee('Draft Tanpa Jadwal');
        $this->get('/trainings?status=akan_dibuka')->assertSee('Pelatihan Desember')->assertDontSee('Pelatihan November');
        $this->get('/trainings?status=final')->assertSee('Belum ada pelatihan yang sesuai filter');
        $this->get('/trainings?status=draft')->assertSee('Draft Tanpa Jadwal')->assertSee('Jadwal belum lengkap');
        $this->get('/trainings?from=2026-12-01&to=2026-12-31')->assertSee('Pelatihan Desember')->assertDontSee('Pelatihan November')->assertDontSee('Draft Tanpa Jadwal');
        $this->get('/trainings?status=bogus')->assertSessionHasErrors('status');

        foreach (['penetapan_peserta', 'pelaksanaan', 'selesai'] as $phase) {
            $this->get('/trainings?status='.$phase)->assertOk()->assertSee('Belum ada pelatihan yang sesuai filter');
        }
    }

    public function test_navigation_shows_pelatihan_for_everyone(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertSee('href="'.route('trainings.index').'"', false);
    }
}
