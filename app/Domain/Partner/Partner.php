<?php

declare(strict_types=1);

namespace App\Domain\Partner;

use App\Domain\Shared\WorkRegion;

/**
 * Mitra pelaksana pelatihan beserta wilayah kerjanya (K18). Data organisasi,
 * tanpa kontak pribadi.
 */
final readonly class Partner
{
    public const MAX_NAME = 150;

    public string $name;

    /**
     * @throws InvalidPartner
     */
    public function __construct(public ?int $id, string $name, public WorkRegion $region)
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));

        if ($name === '' || mb_strlen($name) > self::MAX_NAME) {
            throw new InvalidPartner(sprintf('Nama mitra wajib diisi, maksimal %d karakter.', self::MAX_NAME), 'partner_name');
        }

        $this->name = $name;
    }

    /**
     * Kunci pembanding untuk mencegah duplikat: huruf kecil, tanpa tanda baca,
     * spasi tunggal. "PT. Maju  Jaya" = "pt maju jaya".
     */
    public static function normalize(string $name): string
    {
        $lower = mb_strtolower($name);
        $plain = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $lower);

        return trim($plain);
    }

    public function normalizedName(): string
    {
        return self::normalize($this->name);
    }

    public function rename(string $name, WorkRegion $region): self
    {
        return new self($this->id, $name, $region);
    }
}
