<?php

declare(strict_types=1);

namespace App\Domain\Shared;

final class InvalidUrl extends BusinessRuleViolation
{
    public function __construct(string $field = 'url')
    {
        parent::__construct('Tautan harus diawali http:// atau https:// dan maksimal 2048 karakter.', $field);
    }
}
