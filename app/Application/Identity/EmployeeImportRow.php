<?php

declare(strict_types=1);

namespace App\Application\Identity;

/**
 * Satu baris data mentah dari berkas impor (nomor baris sesuai berkas).
 */
final readonly class EmployeeImportRow
{
    public function __construct(
        public int $line,
        public string $name,
        public string $nip,
        public ?string $pangkatGolongan,
        public ?string $jabatan,
        public ?string $email,
    ) {}
}
