<?php

declare(strict_types=1);

namespace App\Domain\Settings;

use App\Domain\Shared\HttpUrl;
use App\Domain\Shared\InvalidUrl;

/**
 * Pengaturan global yang dikelola Super Admin (keputusan K7).
 */
enum GlobalSetting: string
{
    case DigitalentLoginUrl = 'digitalent_login_url';
    case DigitalentRegisterUrl = 'digitalent_register_url';
    case DtsRegistrationGuideUrl = 'dts_registration_guide_url';
    case ClosingSoonDays = 'closing_soon_days';

    public function label(): string
    {
        return match ($this) {
            self::DigitalentLoginUrl => 'Link login Digitalent',
            self::DigitalentRegisterUrl => 'Link registrasi Digitalent',
            self::DtsRegistrationGuideUrl => 'Link panduan pendaftaran DTS',
            self::ClosingSoonDays => 'Ambang "segera berakhir" (hari sebelum akhir pelatihan)',
        };
    }

    public function defaultValue(): ?string
    {
        return $this === self::ClosingSoonDays ? '7' : null;
    }

    /**
     * Memvalidasi dan menormalkan nilai; null berarti kosong (memakai default).
     *
     * @throws InvalidSetting|InvalidUrl
     */
    public function normalize(?string $raw): ?string
    {
        $raw = $raw === null ? null : trim($raw);

        if ($this === self::ClosingSoonDays) {
            if ($raw === null || $raw === '' || preg_match('/\A\d{1,2}\z/', $raw) !== 1 || (int) $raw < 1 || (int) $raw > 60) {
                throw new InvalidSetting($this, 'Ambang "segera berakhir" harus bilangan bulat 1–60 hari.');
            }

            return (string) (int) $raw;
        }

        return HttpUrl::fromNullable($raw, $this->value)?->value;
    }
}
