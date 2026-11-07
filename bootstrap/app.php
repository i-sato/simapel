<?php

use App\Application\Training\Exceptions\TrainingAccessDenied;
use App\Domain\Shared\BusinessRuleViolation;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global agar halaman 404 (di luar route) juga mendapat header keamanan.
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [EnsureAccountIsActive::class]);
        $middleware->alias(['password.change-required' => RequirePasswordChange::class]);
        $middleware->redirectGuestsTo(fn (): string => route('login'));
        $middleware->redirectUsersTo(fn (): string => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Use case menolak aksi pelatihan yang tidak berhak: tampilkan sebagai 403 biasa.
        $exceptions->render(fn (TrainingAccessDenied $denied) => abort(403));

        // Pelanggaran aturan bisnis adalah kesalahan input pengguna: tampilkan di formulir, jangan dilaporkan sebagai error.
        $exceptions->dontReport(BusinessRuleViolation::class);
        $exceptions->render(function (BusinessRuleViolation $violation, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $violation->getMessage(),
                    'errors' => [$violation->field() => [$violation->getMessage()]],
                ], 422);
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'current_password']))
                ->withErrors([$violation->field() => $violation->getMessage()]);
        });
    })->create();
