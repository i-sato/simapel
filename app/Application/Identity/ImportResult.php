<?php

declare(strict_types=1);

namespace App\Application\Identity;

final readonly class ImportResult
{
    /**
     * @param  list<ImportRowError>  $errors
     */
    private function __construct(
        public int $created,
        public array $errors,
    ) {}

    public static function succeeded(int $created): self
    {
        return new self($created, []);
    }

    /**
     * @param  list<ImportRowError>  $errors
     */
    public static function failed(array $errors): self
    {
        return new self(0, $errors);
    }

    public function isSuccessful(): bool
    {
        return $this->errors === [];
    }
}
