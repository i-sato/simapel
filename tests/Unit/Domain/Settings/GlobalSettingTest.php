<?php

namespace Tests\Unit\Domain\Settings;

use App\Domain\Settings\GlobalSetting;
use App\Domain\Settings\InvalidSetting;
use App\Domain\Shared\InvalidUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GlobalSettingTest extends TestCase
{
    public function test_urls_are_trimmed_and_empty_means_unset(): void
    {
        $this->assertSame('https://digitalent.komdigi.go.id/login', GlobalSetting::DigitalentLoginUrl->normalize('  https://digitalent.komdigi.go.id/login '));
        $this->assertNull(GlobalSetting::DtsRegistrationGuideUrl->normalize(''));
        $this->assertNull(GlobalSetting::DtsRegistrationGuideUrl->normalize(null));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeUrls(): iterable
    {
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'tanpa skema' => ['digitalent.komdigi.go.id'];
        yield 'ftp' => ['ftp://example.test/file'];
        yield 'terlalu panjang' => ['https://example.test/'.str_repeat('a', 2050)];
    }

    #[DataProvider('unsafeUrls')]
    public function test_rejects_non_http_urls(string $url): void
    {
        try {
            GlobalSetting::DigitalentRegisterUrl->normalize($url);
            $this->fail('URL tidak aman seharusnya ditolak.');
        } catch (InvalidUrl $exception) {
            $this->assertSame('digitalent_register_url', $exception->field());
        }
    }

    public function test_closing_soon_days_default_and_range(): void
    {
        $this->assertSame('7', GlobalSetting::ClosingSoonDays->defaultValue());
        $this->assertSame('14', GlobalSetting::ClosingSoonDays->normalize(' 14 '));

        foreach (['0', '61', '', 'tujuh', '7.5', '007'] as $invalid) {
            try {
                GlobalSetting::ClosingSoonDays->normalize($invalid);
                $this->fail("Nilai {$invalid} seharusnya ditolak.");
            } catch (InvalidSetting $exception) {
                $this->assertSame('closing_soon_days', $exception->field());
            }
        }
    }
}
