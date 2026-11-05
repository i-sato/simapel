<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun dengan kata sandi awal dari administrator wajib menggantinya sebelum
 * dapat membuka halaman lain.
 */
final class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User
            && $user->password_change_required
            && ! $request->routeIs('account.password.*', 'logout')) {
            return redirect()->route('account.password.edit')
                ->with('warning', 'Anda wajib mengganti kata sandi awal sebelum melanjutkan.');
        }

        return $next($request);
    }
}
