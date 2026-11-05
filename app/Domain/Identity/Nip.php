<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use Stringable;

/**
 * Nomor Induk Pegawai: tepat 18 digit angka.
 *
 * NIP adalah data pribadi; gunakan masked() untuk tampilan daftar dan log.
 */
final readonly class Nip implements Stringable
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = trim($value);

        if (! self::isValid($normalized)) {
            throw new InvalidNip;
        }

        return new self($normalized);
    }

    public static function isValid(string $value): bool
    {
        return preg_match('/\A[0-9]{18}\z/', $value) === 1;
    }

    public function masked(): string
    {
        return substr($this->value, 0, 4).str_repeat('*', 10).substr($this->value, -4);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
