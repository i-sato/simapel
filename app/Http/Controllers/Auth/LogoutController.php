<?php

namespace App\Http\Controllers\Auth;

use App\Application\Audit\Contracts\AuditTrail;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutController extends Controller
{
    public function __invoke(Request $request, AuditTrail $audit): RedirectResponse
    {
        $userId = $request->user()?->getAuthIdentifier();

        if ($userId !== null) {
            $audit->record(new AuditEvent(
                type: AuditEventType::LoggedOut,
                description: 'Keluar dari aplikasi.',
                actorId: (int) $userId,
                subjectType: 'user',
                subjectId: (int) $userId,
            ));
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari SIMAPEL.');
    }
}
