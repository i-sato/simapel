<?php

namespace Tests\Feature\Database;

use App\Application\Shared\Contracts\Clock;
use App\Domain\Administration\DocumentStatus;
use App\Domain\Sop\SopProvisionStatus;
use App\Domain\Training\TrainingStatus;
use App\Models\AdministrativeDocumentRecord;
use App\Models\LearningPathRecord;
use App\Models\PartnerRecord;
use App\Models\SopProvisionRecord;
use App\Models\TeamMembership;
use App\Models\TrainingAssignmentRecord;
use App\Models\TrainingRecord;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\Fakes\FixedClock;
use Tests\TestCase;

final class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(Clock::class, new FixedClock(new DateTimeImmutable('2026-11-07 09:00')));
    }

    public function test_seeds_consistent_synthetic_demo_data(): void
    {
        $this->seed(DemoSeeder::class);

        // Pegawai sintetis (NIP 9999…), satu nonaktif.
        $this->assertSame(20, User::query()->where('nip', 'like', DemoSeeder::NIP_PREFIX.'%')->count());
        $this->assertSame(0, User::query()->where('nip', 'not like', '9999%')->count());
        $this->assertSame(1, User::query()->where('is_active', false)->count());

        // Tiga tim 2026 masing-masing dengan Ketua dan Sekretaris.
        $this->assertSame(3, TeamMembership::query()->where('role', 'ketua')->count());
        $this->assertSame(3, TeamMembership::query()->where('role', 'sekretaris')->count());

        // Ketentuan Berlaku per tim; DTA memiliki versi Diganti (revisi tengah tahun).
        $this->assertSame(['ASN', 'DTA', 'KDD'], SopProvisionRecord::query()->where('status', SopProvisionStatus::Berlaku)->orderBy('team_code')->pluck('team_code')->map->value->all());
        $this->assertSame(1, SopProvisionRecord::query()->where('team_code', 'DTA')->where('status', SopProvisionStatus::Diganti)->count());

        // Pelatihan: Final dan Draft, satu learning path tiga level, mitra Bali.
        $this->assertSame(9, TrainingRecord::query()->count());
        $this->assertSame(6, TrainingRecord::query()->where('status', TrainingStatus::Final)->count());
        $path = LearningPathRecord::query()->sole();
        $this->assertSame(['dasar', 'menengah', 'mahir'], TrainingRecord::query()->where('learning_path_id', $path->id)->orderBy('id')->pluck('level')->map->value->all());
        $this->assertSame(2, PartnerRecord::query()->count());
        $this->assertSame('bali', TrainingRecord::query()->where('name', 'Demo Analisis Data Pemerintahan')->value('region')?->value);

        // Dokumen administrasi beragam dan bentrok penugasan dengan alasan.
        $this->assertSame(18, AdministrativeDocumentRecord::query()->count());
        $statuses = AdministrativeDocumentRecord::query()->pluck('status')->map->value->unique()->sort()->values()->all();
        $this->assertSame(['belum', 'dalam_proses', 'selesai'], $statuses);
        $this->assertGreaterThanOrEqual(3, TrainingAssignmentRecord::query()->whereNotNull('force_reason')->count());
        $this->assertSame(DocumentStatus::Selesai, AdministrativeDocumentRecord::query()->whereHas('assignee')->where('number', 'like', 'DEMO/BAP/selesai')->value('status'));
    }

    public function test_demo_accounts_can_log_in_and_see_the_dashboard(): void
    {
        $this->seed(DemoSeeder::class);

        $ketua = User::query()->where('nip', DemoSeeder::nip('ketua_kdd'))->sole();
        $this->actingAs($ketua)->get('/dashboard')->assertOk()->assertSee('Pelatihan berjalan');
        $this->get('/workload')->assertOk()->assertSee('Demo Pegawai Satu');
        $this->get('/trainings')->assertOk()->assertSee('Demo Keamanan Siber Dasar')->assertSee('Link peserta kosong');

        $sekretaris = User::query()->where('nip', DemoSeeder::nip('sekretaris_kdd'))->sole();
        $this->actingAs($sekretaris)->get('/dashboard')->assertSee('Demo Keamanan Siber Dasar');

        // Skenario UAT E3: Admin yang ditugaskan memfinalisasi draft Level 2.
        $admin = User::query()->where('nip', DemoSeeder::nip('admin_lintas'))->sole();
        $level2 = TrainingRecord::query()->where('name', 'Demo VBL Web Developer Level 2')->sole();
        $this->actingAs($admin)->get("/trainings/{$level2->id}")->assertSee('Finalisasi</button>', false)->assertDontSee('Atur penugasan');
        $this->post("/trainings/{$level2->id}/finalize")->assertSessionHasNoErrors();
        $this->assertSame(TrainingStatus::Final, $level2->refresh()->status);
    }

    public function test_refuses_to_run_twice_or_outside_local_and_testing(): void
    {
        $this->seed(DemoSeeder::class);

        try {
            $this->seed(DemoSeeder::class);
            $this->fail('Seeder kedua kalinya seharusnya ditolak.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('make demo', $exception->getMessage());
        }

        $this->app['env'] = 'production';
        $this->expectException(RuntimeException::class);
        (new DemoSeeder)->run();
    }
}
