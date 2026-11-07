<?php

namespace Tests\Feature\Training;

use App\Application\Shared\Contracts\Clock;
use App\Domain\Administration\DocumentStatus;
use App\Domain\Administration\DocumentType;
use App\Domain\Assignment\AssignmentRole;
use App\Domain\Audit\AuditEventType;
use App\Domain\Organization\TeamRole;
use App\Domain\Sop\SopProvisionStatus;
use App\Domain\Training\TrainingStatus;
use App\Models\AdministrativeDocumentRecord;
use App\Models\AuditLog;
use App\Models\SopProvisionRecord;
use App\Models\TrainingAssignmentRecord;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Models\WorkTeam;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FixedClock;
use Tests\TestCase;

final class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $ketua;

    private User $sekretaris;

    private User $anggota;

    private TrainingRecord $training;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock('2026-11-20 09:00');

        $team = WorkTeam::query()->where('code', 'DTA')->where('year', 2026)->sole();
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->ketua = User::factory()->create(['name' => 'Ketua DTA']);
        $this->sekretaris = User::factory()->create(['name' => 'Sekretaris DTA']);
        $this->anggota = User::factory()->create(['name' => 'Anggota DTA']);

        foreach ([[$this->ketua, TeamRole::Ketua], [$this->sekretaris, TeamRole::Sekretaris], [$this->anggota, TeamRole::Anggota]] as [$user, $role]) {
            $team->memberships()->create(['user_id' => $user->id, 'role' => $role]);
        }

        SopProvisionRecord::query()->create([
            'team_code' => 'DTA', 'fiscal_year' => 2026, 'version' => 1, 'status' => SopProvisionStatus::Berlaku,
            'sop_number' => 'SOP/DTA/2026', 'sop_effective_date' => '2026-01-01', 'sop_url' => 'https://drive.example.test/sop-dta',
            'effective_from' => '2026-01-01', 'bap_days' => 1, 'sk_days' => 2, 'st_days' => 2, 'ready_days' => 3,
        ]);

        $this->training = TrainingRecord::query()->create([
            'work_team_id' => $team->id, 'status' => TrainingStatus::Draft, 'name' => 'Pelatihan Administrasi',
            'registration_url' => 'https://digitalent.example.test/daftar', 'registration_start' => '2026-12-01', 'registration_end' => '2026-12-10',
            'training_start' => '2026-12-15', 'training_end' => '2026-12-17', 'partner_location' => 'Mitra', 'method' => 'daring', 'batch' => 1,
            'syllabus_url' => 'https://drive.example.test/silabus', 'level' => 'dasar', 'certification' => 'industri', 'coc_shows_score' => true,
            'execution' => 'swakelola', 'participant_category' => 'umum', 'zonation' => 'wilayah_kerja', 'lesson_hours' => 32,
            'participant_target' => 100, 'registration_quota' => 300,
        ]);

        $this->actingAs($this->superAdmin)->post("/trainings/{$this->training->id}/finalize")->assertSessionHasNoErrors();
    }

    private function clock(string $now): void
    {
        $this->app->instance(Clock::class, new FixedClock(new DateTimeImmutable($now)));
    }

    private function document(DocumentType $type): AdministrativeDocumentRecord
    {
        return AdministrativeDocumentRecord::query()->where('training_id', $this->training->id)->where('type', $type)->sole();
    }

    private function url(AdministrativeDocumentRecord $document, string $suffix = ''): string
    {
        return "/trainings/{$this->training->id}/documents/{$document->id}{$suffix}";
    }

    /**
     * @return array<string, string>
     */
    private function done(): array
    {
        return ['status' => 'selesai', 'number' => 'SK/12/2026', 'date' => '2026-12-12', 'file_url' => 'https://drive.example.test/sk'];
    }

    public function test_finalization_opens_three_documents_shown_on_detail(): void
    {
        $this->assertSame(['bap', 'sk', 'st'], AdministrativeDocumentRecord::query()->where('training_id', $this->training->id)->orderBy('type')->pluck('type')->map->value->all());

        $this->actingAs($this->anggota)->get("/trainings/{$this->training->id}")
            ->assertOk()
            ->assertSee('Administrasi')
            ->assertSee('0/3 selesai')
            ->assertSee('11-12-2026 (H+1)')
            ->assertSee('12-12-2026 (H+2)')
            ->assertSee('administrasi siap 13-12-2026 (H+3)')
            ->assertSee('SOP/DTA/2026')
            ->assertDontSee('Delegasikan')
            ->assertDontSee('Perbarui<span', false);
    }

    public function test_secretary_updates_only_documents_delegated_to_them(): void
    {
        $sk = $this->document(DocumentType::Sk);
        $bap = $this->document(DocumentType::Bap);

        $this->actingAs($this->sekretaris)->get($this->url($sk, '/edit'))->assertForbidden();
        $this->put($this->url($sk), $this->done())->assertForbidden();
        $this->post($this->url($sk, '/delegate'), ['assignee_id' => $this->sekretaris->id])->assertForbidden();

        $this->actingAs($this->ketua)->get("/trainings/{$this->training->id}")->assertSee('Delegasikan')->assertSee('Sekretaris DTA (Sekretaris)');
        $this->post($this->url($sk, '/delegate'), ['assignee_id' => $this->sekretaris->id])->assertSessionHasNoErrors();
        $this->from("/trainings/{$this->training->id}")->post($this->url($bap, '/delegate'), ['assignee_id' => $this->anggota->id])->assertSessionHasErrors('assignee_id');

        $this->actingAs($this->sekretaris)->get($this->url($sk, '/edit'))->assertOk();
        $this->from($this->url($sk, '/edit'))->put($this->url($sk), ['status' => 'selesai'])->assertSessionHasErrors('number');
        $this->put($this->url($sk), $this->done())->assertRedirect("/trainings/{$this->training->id}");
        $this->assertSame(DocumentStatus::Selesai, $sk->refresh()->status);
        $this->get($this->url($bap, '/edit'))->assertForbidden();

        $this->actingAs($this->anggota)->put($this->url($sk), $this->done())->assertForbidden();
        $this->actingAs($this->ketua)->put($this->url($bap), ['status' => 'dalam_proses'])->assertSessionHasNoErrors();

        $types = AuditLog::query()->whereIn('event_type', [AuditEventType::DocumentDelegated, AuditEventType::DocumentUpdated])->orderBy('id')->pluck('event_type')->map->value->all();
        $this->assertSame(['administrative_document.delegated', 'administrative_document.updated', 'administrative_document.updated'], $types);
    }

    public function test_document_must_belong_to_training_in_url(): void
    {
        $other = TrainingRecord::query()->create(['work_team_id' => $this->training->work_team_id, 'status' => TrainingStatus::Draft, 'name' => 'Lain']);
        $sk = $this->document(DocumentType::Sk);

        $this->actingAs($this->superAdmin)->get("/trainings/{$other->id}/documents/{$sk->id}/edit")->assertNotFound();
        $this->put("/trainings/{$other->id}/documents/{$sk->id}", $this->done())->assertNotFound();
    }

    public function test_alerts_on_list_detail_and_dashboard(): void
    {
        $sk = $this->document(DocumentType::Sk);
        $this->actingAs($this->ketua)->post($this->url($sk, '/delegate'), ['assignee_id' => $this->sekretaris->id]);
        $admin = User::factory()->create(['can_be_admin' => true, 'name' => 'Admin Pelatihan']);
        TrainingAssignmentRecord::query()->create(['training_id' => $this->training->id, 'user_id' => $admin->id, 'role' => AssignmentRole::Admin]);

        $this->clock('2026-12-14 08:00');

        $this->actingAs($this->anggota)->get('/trainings')
            ->assertSee('Administrasi kritis')
            ->assertSee('BAP lewat tenggat')
            ->assertSee('Link peserta kosong');

        $this->get('/dashboard')
            ->assertSee('Pelatihan berjalan')
            ->assertSee('Pelatihan Administrasi')
            ->assertSee('Administrasi kritis')
            ->assertSee('Tidak ada pekerjaan yang menunggu Anda.');

        $this->actingAs($this->sekretaris)->get('/dashboard')
            ->assertSee('SK peserta · Belum')
            ->assertDontSee('Tidak ada pekerjaan yang menunggu Anda.')
            ->assertSee('tenggat 12-12-2026');

        $this->actingAs($admin)->get('/dashboard')->assertSee('Anda Admin pelatihan ini');

        $this->clock('2026-12-16 08:00');
        $this->actingAs($this->anggota)->get("/trainings/{$this->training->id}")->assertSee('Segera berakhir')->assertSee('padahal pelatihan sudah dimulai');
    }

    public function test_training_without_snapshot_shows_deadline_not_set(): void
    {
        $this->training->forceFill(['sop_provision_id' => null, 'sop_fiscal_year' => null, 'sop_version' => null, 'sop_bap_days' => null, 'sop_sk_days' => null, 'sop_st_days' => null, 'sop_ready_days' => null])->save();

        $this->actingAs($this->anggota)->get("/trainings/{$this->training->id}")->assertSee('Tenggat belum ditetapkan');
        $this->get('/trainings')->assertSee('Tenggat belum ditetapkan');
    }
}
