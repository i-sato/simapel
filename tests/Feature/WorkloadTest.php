<?php

namespace Tests\Feature;

use App\Application\Shared\Contracts\Clock;
use App\Domain\Organization\TeamRole;
use App\Domain\Training\TrainingStatus;
use App\Models\User;
use App\Models\WorkTeam;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fakes\FixedClock;
use Tests\TestCase;

final class WorkloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(Clock::class, new FixedClock(new DateTimeImmutable('2026-11-07 09:00')));
    }

    private function team(string $code): WorkTeam
    {
        return WorkTeam::query()->where('code', $code)->where('year', 2026)->sole();
    }

    private function training(string $team, string $name, string $from, string $to): int
    {
        return DB::table('trainings')->insertGetId([
            'work_team_id' => $this->team($team)->id, 'status' => TrainingStatus::Draft->value, 'name' => $name,
            'registration_start' => $from, 'registration_end' => $from, 'training_start' => $to, 'training_end' => $to,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function assign(int $training, int $user, string $role): void
    {
        DB::table('training_assignments')->insert(['training_id' => $training, 'user_id' => $user, 'role' => $role, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_every_employee_sees_matrix_and_details(): void
    {
        $ketua = User::factory()->create(['name' => 'Ketua DTA']);
        $panitia = User::factory()->create(['name' => 'Panitia Sibuk', 'jabatan' => 'Pranata Komputer']);
        $idle = User::factory()->create(['name' => 'Pegawai Luang']);
        User::factory()->inactive()->create(['name' => 'Pegawai Nonaktif']);
        $this->team('DTA')->memberships()->create(['user_id' => $ketua->id, 'role' => TeamRole::Ketua]);

        $dta = $this->training('DTA', 'Pelatihan DTA', '2026-11-02', '2026-11-12');
        $kdd = $this->training('KDD', 'Pelatihan KDD', '2026-11-10', '2026-11-20');
        $this->assign($dta, $panitia->id, 'panitia');
        $this->assign($kdd, $panitia->id, 'admin');

        $this->actingAs($idle)->get('/workload?from=2026-11-02&to=2026-11-22')
            ->assertOk()
            ->assertSee('Matriks pegawai × periode')
            ->assertSee('2–8 Nov')
            ->assertSee('Panitia Sibuk')
            ->assertSee('Pegawai Luang')
            ->assertDontSee('Pegawai Nonaktif')
            ->assertSee('load-cell load-2 load-conflict', false)
            ->assertSee('⚠ Bentrok', false);

        $this->get('/workload?from=2026-11-01&to=2026-11-30&granularity=bulan&team=KDD')
            ->assertOk()
            ->assertSee('Nov 2026')
            ->assertSee('Panitia Sibuk')
            ->assertDontSee('Ketua DTA');

        $this->get("/workload/{$panitia->id}?from=2026-11-01&to=2026-11-30")
            ->assertOk()
            ->assertSee('Pranata Komputer')
            ->assertSee('Pelatihan DTA')
            ->assertSee('Pelatihan KDD')
            ->assertSee('⚠ Bentrok', false);

        $this->get("/workload/{$ketua->id}")->assertOk()->assertSee('Ketua tim')->assertSee('Pelatihan DTA');

        $this->get('/workload?from=&to=&team=&granularity=')->assertOk()->assertSee('1 November 2026–31 Januari 2027');
        $this->get('/workload?from=2026-12-01&to=2026-11-01')->assertSessionHasErrors('to');
        $this->get('/workload?granularity=tahunan')->assertSessionHasErrors('granularity');
        $this->get('/dashboard')->assertSee('href="'.route('workload.index').'"', false);
    }

    public function test_secretary_with_crew_duty_in_other_team_is_flagged(): void
    {
        $sekretaris = User::factory()->create(['name' => 'Sekretaris DTA']);
        $this->team('DTA')->memberships()->create(['user_id' => $sekretaris->id, 'role' => TeamRole::Sekretaris]);
        $this->training('DTA', 'Pelatihan DTA A', '2026-11-02', '2026-11-12');
        $this->training('DTA', 'Pelatihan DTA B', '2026-11-05', '2026-11-14');

        // Dua tugas Sekretaris di tim sendiri: bukan bentrok (K19).
        $this->actingAs($sekretaris)->get("/workload/{$sekretaris->id}?from=2026-11-01&to=2026-11-30")
            ->assertSee('Sekretaris tim')->assertDontSee('⚠ Bentrok', false);

        $kdd = $this->training('KDD', 'Pelatihan KDD', '2026-11-10', '2026-11-20');
        $this->assign($kdd, $sekretaris->id, 'panitia');

        $this->get("/workload/{$sekretaris->id}?from=2026-11-01&to=2026-11-30")->assertSee('⚠ Bentrok', false);
        $this->get('/workload?from=2026-11-01&to=2026-11-30&granularity=bulan')->assertSee('load-cell load-3 load-conflict', false);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get('/workload')->assertRedirect('/login');
    }

    public function test_query_count_is_constant_for_100_employees_and_50_trainings(): void
    {
        $viewer = User::factory()->create();
        $countFor = function () use ($viewer): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($viewer)->get('/workload?granularity=minggu')->assertOk();
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };

        $baseline = $countFor();

        $users = User::factory()->count(100)->create();
        $ids = $users->pluck('id')->all();
        $teams = ['DTA', 'KDD', 'ASN'];

        foreach ($teams as $index => $code) {
            $this->team($code)->memberships()->create(['user_id' => $ids[$index], 'role' => TeamRole::Ketua]);
            $this->team($code)->memberships()->create(['user_id' => $ids[$index + 3], 'role' => TeamRole::Sekretaris]);
        }

        $assignments = [];

        for ($t = 0; $t < 50; $t++) {
            $start = (new DateTimeImmutable('2026-11-01'))->modify('+'.($t % 60).' days');
            $training = $this->training($teams[$t % 3], 'Pelatihan Sintetis '.$t, $start->format('Y-m-d'), $start->modify('+10 days')->format('Y-m-d'));

            for ($k = 0; $k < 4; $k++) {
                $assignments[] = ['training_id' => $training, 'user_id' => $ids[($t * 7 + $k * 13) % 100], 'role' => $k === 0 ? 'admin' : 'panitia', 'created_at' => now(), 'updated_at' => now()];
            }
        }

        DB::table('training_assignments')->insertOrIgnore($assignments);

        $loaded = $countFor();

        // Jumlah query tidak bertambah seiring data (tanpa N+1); mode ketat Eloquent aktif.
        $this->assertSame($baseline, $loaded);
        $this->assertLessThanOrEqual(12, $loaded);

        $first = $users->first();
        $this->assertNotNull($first);
        $this->get('/workload?granularity=minggu')->assertSee($first->name);
    }
}
