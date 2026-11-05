<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk semua respons dinamis (OWASP Secure Headers).
 *
 * CSP tanpa 'unsafe-inline': seluruh CSS/JS wajib berupa file di public/,
 * tanpa atribut style="" maupun handler onclick="" di Blade.
 */
final class SecurityHeaders
{
    private const CONTENT_SECURITY_POLICY = [
        "default-src 'self'",
        "script-src 'self'",
        "style-src 'self'",
        "img-src 'self' data:",
        "font-src 'self'",
        "connect-src 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "object-src 'none'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $headers = $response->headers;

        // Halaman debug Laravel memakai inline asset; CSP dilonggarkan hanya untuk error 5xx saat APP_DEBUG=true.
        if (! (config('app.debug') && $response->getStatusCode() >= 500)) {
            $policy = self::CONTENT_SECURITY_POLICY;

            if ($request->isSecure()) {
                $policy[] = 'upgrade-insecure-requests';
            }

            $headers->set('Content-Security-Policy', implode('; ', $policy));
        }

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'same-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $headers->remove('X-Powered-By');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->user() !== null) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
