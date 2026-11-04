<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis yang aman ditampilkan ke pengguna.
 *
 * Layer presentasi memetakan exception ini menjadi pesan validasi pada field()
 * yang relevan, tanpa membocorkan detail teknis.
 */
abstract class BusinessRuleViolation extends RuntimeException
{
    public function __construct(string $message, private readonly string $field = 'general')
    {
        parent::__construct($message);
    }

    public function field(): string
    {
        return $this->field;
    }
}
