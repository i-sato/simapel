<?php

namespace Tests\Feature\Infrastructure;

use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\Exceptions\NipAlreadyRegistered;
use App\Application\Identity\NewUserAccount;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Identity\EmployeeProfile;
use App\Domain\Identity\Nip;
use App\Domain\Identity\Role;
use App\Infrastructure\Persistence\EloquentAuditTrail;
use App\Infrastructure\Persistence\EloquentUserAccountRepository;
use App\Infrastructure\Session\DatabaseSessionTerminator;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

final class PersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function newAccount(string $nip, ?string $email): NewUserAccount
    {
        return new NewUserAccount(Nip::fromString($nip), EmployeeProfile::fromInput('Pengguna Uji', $email, null, null), Role::Pegawai, false, bcrypt('Sandi-Uji-2026!'), false);
    }

    public function test_repository_maps_unique_nip_race_to_business_error(): void
    {
        User::factory()->create(['nip' => '999900000000000020']);

        $this->expectException(NipAlreadyRegistered::class);
        app(EloquentUserAccountRepository::class)->create($this->newAccount('999900000000000020', 'baru@example.test'));
    }

    public function test_repository_maps_unique_email_race_to_business_error(): void
    {
        User::factory()->create(['email' => 'sama@example.test']);

        $this->expectException(EmailAlreadyRegistered::class);
        app(EloquentUserAccountRepository::class)->create($this->newAccount('999900000000000021', 'sama@example.test'));
    }

    public function test_recording_login_does_not_touch_profile_timestamp(): void
    {
        $user = User::factory()->create();
        $before = $user->updated_at;

        $this->travel(5)->minutes();
        app(EloquentUserAccountRepository::class)->recordLogin($user->id, now()->toImmutable());

        $fresh = $user->fresh();
        $this->assertNotNull($fresh?->last_login_at);
        $this->assertEquals($before, $fresh->updated_at);
    }

    public function test_audit_log_is_append_only(): void
    {
        app(EloquentAuditTrail::class)->record(new AuditEvent(AuditEventType::LoginFailed, 'Login gagal.', context: ['nip_masked' => '9999**********0001']));
        $log = AuditLog::query()->sole();

        $this->assertSame('cli', $log->channel);
        $this->assertNull($log->ip_address);

        try {
            $log->update(['description' => 'Diubah']);
            $this->fail('Audit log tidak boleh dapat diubah.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_session_terminator_removes_only_other_sessions_of_the_user(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $other = User::factory()->create();

        foreach ([['aktif', $user->id], ['lama-1', $user->id], ['lama-2', $user->id], ['milik-lain', $other->id]] as [$id, $owner]) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $owner, 'payload' => '', 'last_activity' => time()]);
        }

        $terminator = app(DatabaseSessionTerminator::class);

        $this->assertSame(2, $terminator->terminateOtherSessions($user->id, 'aktif'));
        $this->assertSame(['aktif', 'milik-lain'], DB::table('sessions')->orderBy('id')->pluck('id')->all());

        $this->assertSame(1, $terminator->terminateAllSessions($other->id));
    }

    public function test_session_terminator_is_noop_for_non_database_drivers(): void
    {
        config(['session.driver' => 'array']);

        $this->assertSame(0, app(DatabaseSessionTerminator::class)->terminateAllSessions(1));
    }
}
