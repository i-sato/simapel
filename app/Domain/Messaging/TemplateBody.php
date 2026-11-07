<?php

declare(strict_types=1);

namespace App\Domain\Messaging;

/**
 * Isi template kustom yang tervalidasi:
 * - setiap {{...}} harus placeholder daftar putih yang ditulis persis (tanpa spasi);
 * - wajib memuat link pelatihan, tanggal pendaftaran, tanggal pelatihan, dan link panduan;
 * - kata "gratis" wajib ada di bagian akhir (5 baris berisi terakhir).
 */
final readonly class TemplateBody
{
    public const MAX_LENGTH = 4000;

    public const CLOSING_LINES = 5;

    public string $value;

    /**
     * @throws InvalidMessageTemplate
     */
    public function __construct(string $body)
    {
        $body = self::normalize($body);

        if ($body === '') {
            throw new InvalidMessageTemplate('Isi template wajib diisi.');
        }

        if (mb_strlen($body) > self::MAX_LENGTH) {
            throw new InvalidMessageTemplate(sprintf('Template maksimal %d karakter.', self::MAX_LENGTH));
        }

        $unknown = self::unknownTokens($body);

        if ($unknown !== []) {
            throw new InvalidMessageTemplate('Placeholder tidak dikenal: '.implode(', ', $unknown).'. Gunakan placeholder dari daftar yang tersedia.');
        }

        $missing = array_values(array_filter(
            Placeholder::required(),
            fn (Placeholder $placeholder): bool => ! str_contains($body, $placeholder->token()),
        ));

        if ($missing !== []) {
            throw new InvalidMessageTemplate('Template wajib memuat '.implode(', ', array_map(fn (Placeholder $p): string => $p->token(), $missing)).'.');
        }

        if (! self::closesWithFreeStatement($body)) {
            throw new InvalidMessageTemplate(sprintf('Bagian akhir template (%d baris terakhir) wajib menegaskan bahwa pelatihan "gratis".', self::CLOSING_LINES));
        }

        $this->value = $body;
    }

    /**
     * Bentuk baku untuk penyimpanan dan pembandingan: akhir baris \n, tanpa spasi di ujung baris.
     */
    public static function normalize(string $body): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];

        return trim(implode("\n", array_map(rtrim(...), $lines)));
    }

    public function equals(string $other): bool
    {
        return $this->value === self::normalize($other);
    }

    /**
     * @return list<string>
     */
    private static function unknownTokens(string $body): array
    {
        preg_match_all('/\{\{.*?\}\}|\{!!.*?!!\}/s', $body, $matches);
        $known = array_map(fn (Placeholder $placeholder): string => $placeholder->token(), Placeholder::cases());

        return array_values(array_unique(array_filter($matches[0], fn (string $token): bool => ! in_array($token, $known, true))));
    }

    private static function closesWithFreeStatement(string $body): bool
    {
        $lines = array_values(array_filter(explode("\n", $body), fn (string $line): bool => trim($line) !== ''));
        $closing = implode("\n", array_slice($lines, -self::CLOSING_LINES));

        return preg_match('/\bgratis\b/iu', $closing) === 1;
    }
}
