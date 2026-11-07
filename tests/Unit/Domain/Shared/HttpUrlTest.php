<?php

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\HttpUrl;
use App\Domain\Shared\InvalidUrl;
use PHPUnit\Framework\TestCase;

final class HttpUrlTest extends TestCase
{
    public function test_accepts_http_and_https(): void
    {
        $this->assertSame('https://example.test/a?b=1', HttpUrl::fromString(' https://example.test/a?b=1 ', 'url')->value);
        $this->assertSame('http://example.test', HttpUrl::fromString('http://example.test', 'url')->value);
        $this->assertNull(HttpUrl::fromNullable('  ', 'url'));
    }

    public function test_rejects_other_schemes_with_field_name(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html,x', 'https://', '//example.test'] as $url) {
            try {
                HttpUrl::fromString($url, 'sop_url');
                $this->fail("{$url} seharusnya ditolak.");
            } catch (InvalidUrl $exception) {
                $this->assertSame('sop_url', $exception->field());
            }
        }
    }
}
