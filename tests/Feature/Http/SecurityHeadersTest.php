<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  TestResponse<Response>  $response
     */
    private function assertBaselineHeaders(TestResponse $response): void
    {
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'same-origin');
        $response->assertHeaderMissing('X-Powered-By');
    }

    public function test_guest_pages_carry_security_headers(): void
    {
        $this->assertBaselineHeaders($this->get('/login'));
    }

    public function test_authenticated_pages_are_not_cached(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $this->assertBaselineHeaders($response);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_unknown_routes_still_get_headers_and_themed_404(): void
    {
        $response = $this->get('/halaman-yang-tidak-ada');

        $response->assertNotFound()->assertSee('Halaman tidak ditemukan');
        $this->assertBaselineHeaders($response);
    }

    public function test_hsts_and_upgrade_directive_only_on_https(): void
    {
        $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');

        $secure = $this->get('https://localhost/login');
        $secure->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->assertStringContainsString('upgrade-insecure-requests', (string) $secure->headers->get('Content-Security-Policy'));
    }

    public function test_views_contain_no_inline_styles_or_scripts(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $view) {
            if (preg_match('/\sstyle\s*=|\son[a-z]+\s*=|<script(?![^>]*\ssrc=)|<style/i', $view->getContents()) === 1) {
                $offenders[] = $view->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders, 'CSP melarang style/script inline; pindahkan ke public/css atau public/js.');
    }
}
