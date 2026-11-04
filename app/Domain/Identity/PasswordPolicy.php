<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * Kebijakan kekuatan kata sandi. Batas maksimum dalam byte mengikuti batas bcrypt.
 */
final readonly class PasswordPolicy
{
    public function __construct(
        public int $minLength = 12,
        public int $maxBytes = 72,
    ) {}

    /**
     * @return list<string> daftar pelanggaran; kosong bila kata sandi memenuhi kebijakan
     */
    public function violations(#[\SensitiveParameter] string $password): array
    {
        $violations = [];

        if (mb_strlen($password) < $this->minLength) {
            $violations[] = sprintf('Kata sandi minimal %d karakter.', $this->minLength);
        }

        if (strlen($password) > $this->maxBytes) {
            $violations[] = sprintf('Kata sandi maksimal %d karakter.', $this->maxBytes);
        }

        if (preg_match('/\p{Lu}/u', $password) !== 1 || preg_match('/\p{Ll}/u', $password) !== 1) {
            $violations[] = 'Kata sandi harus mengandung huruf besar dan huruf kecil.';
        }

        if (preg_match('/\p{N}/u', $password) !== 1) {
            $violations[] = 'Kata sandi harus mengandung angka.';
        }

        if (preg_match('/[\p{Z}\p{S}\p{P}]/u', $password) !== 1) {
            $violations[] = 'Kata sandi harus mengandung simbol.';
        }

        return $violations;
    }

    public function assertSatisfiedBy(#[\SensitiveParameter] string $password): void
    {
        $violations = $this->violations($password);

        if ($violations !== []) {
            throw new WeakPassword($violations[0]);
        }
    }

    /**
     * @return list<string>
     */
    public function summary(): array
    {
        return [
            sprintf('Minimal %d karakter', $this->minLength),
            'Mengandung huruf besar dan huruf kecil',
            'Mengandung angka dan simbol',
        ];
    }
}
