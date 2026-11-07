<?php

namespace App\Providers;

use App\Application\Administration\Contracts\AdministrativeDocumentRepository;
use App\Application\Assignment\Contracts\AssignmentCandidateRepository;
use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\SessionTerminator;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Messaging\Contracts\CustomTemplateRepository;
use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Application\Partner\Contracts\PartnerRepository;
use App\Application\Partner\SavePartner;
use App\Application\Settings\Contracts\SettingsRepository;
use App\Application\Shared\Contracts\Clock;
use App\Application\Shared\Contracts\Transaction;
use App\Application\Sop\Contracts\SopProvisionRepository;
use App\Application\Training\Contracts\LearningPathRepository;
use App\Application\Training\Contracts\TrainingRepository;
use App\Application\Training\TrainingAccess;
use App\Domain\Identity\PasswordPolicy;
use App\Infrastructure\Persistence\DatabaseTransaction;
use App\Infrastructure\Persistence\EloquentAdministrativeDocumentRepository;
use App\Infrastructure\Persistence\EloquentAssignmentCandidateRepository;
use App\Infrastructure\Persistence\EloquentAssignmentRepository;
use App\Infrastructure\Persistence\EloquentAuditTrail;
use App\Infrastructure\Persistence\EloquentCustomTemplateRepository;
use App\Infrastructure\Persistence\EloquentLearningPathRepository;
use App\Infrastructure\Persistence\EloquentPartnerRepository;
use App\Infrastructure\Persistence\EloquentSettingsRepository;
use App\Infrastructure\Persistence\EloquentSopProvisionRepository;
use App\Infrastructure\Persistence\EloquentTrainingRepository;
use App\Infrastructure\Persistence\EloquentUserAccountRepository;
use App\Infrastructure\Persistence\EloquentWorkTeamRepository;
use App\Infrastructure\Security\LaravelPasswordHasher;
use App\Infrastructure\Session\DatabaseSessionTerminator;
use App\Infrastructure\Time\SystemClock;
use App\Models\TrainingRecord;
use App\Models\User;
use App\View\Composers\NavigationComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Port (interface) layer Application => implementasi layer Infrastructure.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserAccountRepository::class => EloquentUserAccountRepository::class,
        WorkTeamRepository::class => EloquentWorkTeamRepository::class,
        SettingsRepository::class => EloquentSettingsRepository::class,
        SopProvisionRepository::class => EloquentSopProvisionRepository::class,
        TrainingRepository::class => EloquentTrainingRepository::class,
        LearningPathRepository::class => EloquentLearningPathRepository::class,
        AssignmentRepository::class => EloquentAssignmentRepository::class,
        AssignmentCandidateRepository::class => EloquentAssignmentCandidateRepository::class,
        CustomTemplateRepository::class => EloquentCustomTemplateRepository::class,
        AdministrativeDocumentRepository::class => EloquentAdministrativeDocumentRepository::class,
        PartnerRepository::class => EloquentPartnerRepository::class,
        AuditTrail::class => EloquentAuditTrail::class,
        PasswordHasher::class => LaravelPasswordHasher::class,
        SessionTerminator::class => DatabaseSessionTerminator::class,
        Transaction::class => DatabaseTransaction::class,
        Clock::class => SystemClock::class,
    ];

    public function register(): void
    {
        $this->app->singleton(PasswordPolicy::class, fn (): PasswordPolicy => new PasswordPolicy(
            minLength: (int) config('simapel.password.min_length'),
            maxBytes: (int) config('simapel.password.max_length'),
        ));
    }

    public function boot(): void
    {
        // Di luar production: gagal cepat untuk lazy loading (N+1) dan atribut yang tidak terdaftar.
        Model::shouldBeStrict(! $this->app->isProduction());
        DB::prohibitDestructiveCommands($this->app->isProduction());
        URL::forceHttps($this->app->isProduction());

        Password::defaults(function (): Password {
            $policy = $this->app->make(PasswordPolicy::class);

            return Password::min($policy->minLength)
                ->max($policy->maxBytes)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(20)->by((string) $request->ip()));

        $trustedProxies = config('simapel.trusted_proxies');

        if (is_string($trustedProxies) && trim($trustedProxies) !== '') {
            TrustProxies::at(array_map(trim(...), explode(',', $trustedProxies)));
        }

        // Pengelolaan pegawai, tim kerja, dan pengaturan: hanya Super Admin aktif.
        Gate::define('manage-organization', fn (User $user): bool => $user->isActive() && $user->role->isSuperAdmin());

        // Pelatihan (ADR 0009): aturan ada di App\Domain\Training\TrainingPermissions; use case menegakkan aturan yang sama.
        Gate::define('create-training', fn (User $user): bool => $this->app->make(TrainingAccess::class)->canCreateAny($user->id));
        Gate::define('update-training', fn (User $user, TrainingRecord $training): bool => $this->app->make(TrainingAccess::class)->canEdit($user->id, $training->id));
        Gate::define('manage-training-assignments', fn (User $user, TrainingRecord $training): bool => $this->app->make(TrainingAccess::class)->canManageAssignments($user->id, $training->id));
        Gate::define('use-message-templates', fn (User $user, TrainingRecord $training): bool => $this->app->make(TrainingAccess::class)->canUseMessageTemplates($user->id, $training->id));
        Gate::define('manage-partners', fn (User $user): bool => $this->app->make(SavePartner::class)->canManage($user->id));
        Gate::define('finalize-training', fn (User $user, TrainingRecord $training): bool => $this->app->make(TrainingAccess::class)->canFinalize($user->id, $training->id));

        View::composer('layouts.app', NavigationComposer::class);
    }
}
