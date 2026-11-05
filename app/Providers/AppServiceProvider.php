<?php

namespace App\Providers;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\SessionTerminator;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Shared\Contracts\Clock;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Identity\PasswordPolicy;
use App\Infrastructure\Persistence\DatabaseTransaction;
use App\Infrastructure\Persistence\EloquentAuditTrail;
use App\Infrastructure\Persistence\EloquentUserAccountRepository;
use App\Infrastructure\Security\LaravelPasswordHasher;
use App\Infrastructure\Session\DatabaseSessionTerminator;
use App\Infrastructure\Time\SystemClock;
use App\View\Composers\NavigationComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        View::composer('layouts.app', NavigationComposer::class);
    }
}
