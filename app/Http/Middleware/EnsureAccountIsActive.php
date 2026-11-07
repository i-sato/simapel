<?php

namespace App\Http\Middleware;

use App\Application\Audit\Contracts\AuditTrail;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang dinonaktifkan langsung kehilangan sesinya pada request berikutnya.
 */
final readonly class EnsureAccountIsActive
{
    public function __construct(private AuditTrail $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isActive()) {
            $this->audit->record(new AuditEvent(
                type: AuditEventType::SessionRevoked,
                description: 'Sesi diakhiri karena akun nonaktif.',
                actorId: $user->id,
                subjectType: 'user',
                subjectId: $user->id,
            ));

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['nip' => 'Akun Anda tidak aktif. Hubungi Super Admin.']);
        }

        return $next($request);
    }
}
