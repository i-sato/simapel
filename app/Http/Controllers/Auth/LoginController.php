<?php

namespace App\Http\Controllers\Auth;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Identity\RecordSuccessfulLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditTrail $audit, RecordSuccessfulLogin $recordSuccessfulLogin): RedirectResponse
    {
        $request->authenticate($audit);

        // Cegah session fixation: ID sesi baru setelah autentikasi.
        $request->session()->regenerate();

        $recordSuccessfulLogin->handle((int) $request->user()?->getAuthIdentifier());

        return redirect()->intended(route('dashboard'));
    }
}
