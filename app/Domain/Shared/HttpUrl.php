<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use Stringable;

/**
 * Tautan web yang aman disimpan dan ditampilkan: hanya http/https, maksimal 2048 karakter.
 */
final readonly class HttpUrl implements Stringable
{
    private function __construct(public string $value) {}

    /**
     * @throws InvalidUrl
     */
    public static function fromString(string $value, string $field = 'url'): self
    {
        $value = trim($value);

        if ($value === ''
            || strlen($value) > 2048
            || preg_match('#\Ahttps?://#i', $value) !== 1
            || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidUrl($field);
        }

        return new self($value);
    }

    /**
     * @throws InvalidUrl
     */
    public static function fromNullable(?string $value, string $field = 'url'): ?self
    {
        return $value === null || trim($value) === '' ? null : self::fromString($value, $field);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
