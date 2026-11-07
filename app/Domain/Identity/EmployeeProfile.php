<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * Data profil pegawai sesuai template daftar pegawai
 * (Nama, Pangkat/Gol. Ruang, Jabatan, email). NIP disimpan terpisah sebagai Nip.
 */
final readonly class EmployeeProfile
{
    private function __construct(
        public string $name,
        public ?string $email,
        public ?string $pangkatGolongan,
        public ?string $jabatan,
    ) {}

    /**
     * @throws InvalidEmployeeProfile
     */
    public static function fromInput(string $name, ?string $email, ?string $pangkatGolongan, ?string $jabatan): self
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 255) {
            throw new InvalidEmployeeProfile('Nama wajib diisi dan maksimal 255 karakter.', 'name');
        }

        return new self(
            name: $name,
            email: self::normalizeEmail($email),
            pangkatGolongan: self::optionalText($pangkatGolongan, 100, 'pangkat_golongan', 'Pangkat/golongan'),
            jabatan: self::optionalText($jabatan, 150, 'jabatan', 'Jabatan'),
        );
    }

    private static function normalizeEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        $email = mb_strtolower(trim($email));

        if (mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmployeeProfile('Format email tidak valid.', 'email');
        }

        return $email;
    }

    private static function optionalText(?string $value, int $max, string $field, string $label): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        if (mb_strlen($value) > $max) {
            throw new InvalidEmployeeProfile(sprintf('%s maksimal %d karakter.', $label, $max), $field);
        }

        return $value;
    }
}
