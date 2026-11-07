<?php

declare(strict_types=1);

namespace App\Application\Identity;

final readonly class ImportRowError
{
    /**
     * @param  int  $line  nomor baris pada berkas; 0 untuk kesalahan berkas secara umum
     */
    public function __construct(
        public int $line,
        public string $message,
    ) {}
}
