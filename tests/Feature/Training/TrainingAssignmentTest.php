<?php

namespace Tests\Feature\Training;

use App\Application\Shared\Contracts\Clock;
use App\Domain\Assignment\AssignmentRole;
use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\TeamRole;
use App\Domain\Training\TrainingStatus;
use App\Models\AuditLog;
use App\Models\TrainingAssignmentRecord;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Models\WorkTeam;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FixedClock;
use Tests\TestCase;

final class TrainingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $ketuaDta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(Clock::class, new FixedClock(new DateTimeImmutable('2026-11-04 09:00:00')));
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->ketuaDta = $this->member('DTA', TeamRole::Ketua, ['name' => 'Ketua DTA']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(string $team, TeamRole $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        WorkTeam::query()->where('code', $team)->where('year', 2026)->sole()
            ->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    private function training(string $team, string $name, string $from, string $to): TrainingRecord
    {
        return TrainingRecord::query()->create([
            'work_team_id' => WorkTeam::query()->where('code', $team)->where('year', 2026)->value('id'),
            'status' => TrainingStatus::Draft,
            'name' => $name,
            'registration_url' => 'https://digitalent.example.test/daftar',
            'registration_start' => $from,
            'registration_end' => $from,
            'training_start' => $to,
            'training_end' => $to,
            'partner_location' => 'Mitra Uji',
            'method' => 'daring',
            'batch' => 1,
            'syllabus_url' => 'https://drive.example.test/silabus',
            'level' => 'dasar',
            'certification' => 'industri',
            'coc_shows_score' => true,
            'execution' => 'swakelola',
            'participant_category' => 'umum',
            'zonation' => 'wilayah_kerja',
            'lesson_hours' => 32,
            'participant_target' => 100,
            'registration_quota' => 300,
        ]);
    }

    /**
     * @param  list<int>  $admins
     * @param  list<int>  $panitia
     * @return array<string, mixed>
     */
    private function payload(array $admins = [], array $panitia = [], ?string $reason = null): array
    {
        return array_filter(['admins' => $admins, 'panitia' => $panitia, 'force_reason' => $reason], fn ($value) => $value !== null);
    }

    public function test_access_is_limited_to_team_ketua_and_super_admin(): void
    {
        $training = $this->training('DTA', 'Pelatihan DTA', '2026-12-01', '2026-12-09');
        $url = "/trainings/{$training->id}/assignments";

        $this->actingAs($this->ketuaDta)->get($url)->assertOk()->assertSee('Atur penugasan');
        $this->actingAs($this->superAdmin)->get($url)->assertOk();
        $this->actingAs($this->member('KDD', TeamRole::Ketua))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->put($url, $this->payload(panitia: [$this->ketuaDta->id]))->assertForbidden();

        $this->assertSame(0, TrainingAssignmentRecord::query()->count());
        $this->actingAs(User::factory()->create())->get("/trainings/{$training->id}")->assertOk()->assertDontSee('Atur penugasan');
    }

    public function test_admin_must_have_capability_and_panitia_may_be_other_team_leaders(): void
    {
        $training = $this->training('DTA', 'Pelatihan DTA', '2026-12-01', '2026-12-09');
        $plain = User::factory()->create(['name' => 'Pegawai Biasa']);
        $capable = User::factory()->create(['name' => 'Calon Admin', 'can_be_admin' => true]);
        $ketuaKdd = $this->member('KDD', TeamRole::Ketua, ['name' => 'Ketua KDD']);
        $sekretarisAsn = $this->member('ASN', TeamRole::Sekretaris, ['name' => 'Sekretaris ASN']);
        $inactive = User::factory()->inactive()->create(['name' => 'Pegawai Nonaktif']);
        $this->actingAs($this->ketuaDta)->from("/trainings/{$training->id}/assignments");

        $this->get("/trainings/{$training->id}/assignments")
            ->assertSee('Ketua Tim KDD')
            ->assertSee('Sekretaris Tim ASN')
            ->assertSee('name="admins[]" value="'.$plain->id.'"  disabled', false)
            ->assertDontSee('Pegawai Nonaktif');

        $this->put("/trainings/{$training->id}/assignments", $this->payload(admins: [$plain->id]))->assertSessionHasErrors('admins');
        $this->put("/trainings/{$training->id}/assignments", $this->payload(panitia: [$inactive->id]))->assertSessionHasErrors('panitia');
        $this->assertSame(0, TrainingAssignmentRecord::query()->count());

        $this->put("/trainings/{$training->id}/assignments", $this->payload(admins: [$capable->id], panitia: [$ketuaKdd->id, $sekretarisAsn->id, $plain->id]))
            ->assertRedirect("/trainings/{$training->id}")
            ->assertSessionHas('success', 'Penugasan disimpan: 4 ditambahkan, 0 dicabut.');

        $this->get("/trainings/{$training->id}")->assertSee('Calon Admin')->assertSee('Ketua Tim KDD');
        $this->get("/trainings/{$training->id}/assignments")->assertSee('name="admins[]" value="'.$capable->id.'" checked', false);

        $this->put("/trainings/{$training->id}/assignments", $this->payload(admins: [$capable->id], panitia: [$ketuaKdd->id]))
            ->assertSessionHas('success', 'Penugasan disimpan: 0 ditambahkan, 2 dicabut.');
        $this->assertSame(2, AuditLog::query()->where('event_type', AuditEventType::TrainingAssignmentRemoved)->count());
    }

    public function test_conflicting_assignment_requires_force_reason(): void
    {
        $panitia = User::factory()->create(['name' => 'Panitia Sibuk']);
        $kdd = $this->training('KDD', 'Pelatihan KDD', '2026-11-01', '2026-11-09');
        $dta = $this->training('DTA', 'Pelatihan DTA', '2026-11-09', '2026-11-20');
        $this->actingAs($this->superAdmin)->put("/trainings/{$kdd->id}/assignments", $this->payload(panitia: [$panitia->id]))->assertSessionHasNoErrors();

        $this->actingAs($this->ketuaDta)->from("/trainings/{$dta->id}/assignments");
        $this->get("/trainings/{$dta->id}/assignments")
            ->assertSee('Ada pegawai dengan penugasan yang beririsan')
            ->assertSee('Panitia KDD · 1–9 Nov');

        $this->put("/trainings/{$dta->id}/assignments", $this->payload(panitia: [$panitia->id]))
            ->assertSessionHasErrors(['force_reason' => 'Penugasan bentrok dengan pelatihan lain. Isi alasan untuk tetap menugaskan. Panitia Sibuk (Panitia): Panitia KDD · 1–9 Nov "Pelatihan KDD".']);
        $this->assertSame(0, TrainingAssignmentRecord::query()->where('training_id', $dta->id)->count());

        $this->put("/trainings/{$dta->id}/assignments", $this->payload(panitia: [$panitia->id], reason: 'Satu-satunya fasilitator data'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Satu-satunya fasilitator data', TrainingAssignmentRecord::query()->where('training_id', $dta->id)->value('force_reason'));
        $log = AuditLog::query()->where('event_type', AuditEventType::TrainingAssignmentAdded)->where('subject_id', $dta->id)->sole();
        $this->assertSame('Satu-satunya fasilitator data', $log->context['force_reason'] ?? null);

        $this->get("/trainings/{$dta->id}")->assertSee('Alasan bentrok: Satu-satunya fasilitator data')->assertSee('Panitia KDD · 1–9 Nov');

        // Pelatihan di luar rentang tidak bentrok.
        $later = $this->training('ASN', 'Pelatihan ASN', '2026-11-21', '2026-11-30');
        $this->actingAs($this->superAdmin)->put("/trainings/{$later->id}/assignments", $this->payload(panitia: [$panitia->id]))->assertSessionHasNoErrors();
    }

    public function test_assigned_admin_finalizes_and_updates_participant_list(): void
    {
        $admin = User::factory()->create(['can_be_admin' => true]);
        $training = $this->training('DTA', 'Pelatihan DTA', '2026-11-01', '2026-11-20');

        $this->actingAs($admin)->post("/trainings/{$training->id}/finalize")->assertForbidden();

        $this->actingAs($this->ketuaDta)->put("/trainings/{$training->id}/assignments", $this->payload(admins: [$admin->id]));

        $this->actingAs($admin)->get("/trainings/{$training->id}")->assertSee('Finalisasi</button>', false)->assertDontSee('Atur penugasan');
        $this->post("/trainings/{$training->id}/finalize")->assertRedirect("/trainings/{$training->id}");
        $this->assertSame(TrainingStatus::Final, $training->refresh()->status);

        $this->get("/trainings/{$training->id}/edit")->assertOk();
        $this->get("/trainings/{$training->id}/assignments")->assertForbidden();

        // Tanda "dapat menjadi Admin" dicabut Super Admin: hak Admin pada pelatihan hilang.
        $admin->forceFill(['can_be_admin' => false])->save();
        $this->get("/trainings/{$training->id}/edit")->assertForbidden();
        $this->assertSame(AssignmentRole::Admin, TrainingAssignmentRecord::query()->where('user_id', $admin->id)->value('role'));
    }
}
