<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Jam dan menit (00:00–23:59), mis. jam penutupan pendaftaran.
 */
final readonly class TimeOfDay
{
    private function __construct(public int $hour, public int $minute) {}

    /**
     * @throws InvalidTimeOfDay
     */
    public static function fromString(string $value, string $field = 'time'): self
    {
        $value = trim($value);

        if (preg_match('/\A([01]\d|2[0-3])[:.]([0-5]\d)(?::00)?\z/', $value, $matches) !== 1) {
            throw new InvalidTimeOfDay($field);
        }

        return new self((int) $matches[1], (int) $matches[2]);
    }

    public static function endOfDay(): self
    {
        return new self(23, 59);
    }

    /** Format HH:MM untuk input dan penyimpanan. */
    public function value(): string
    {
        return sprintf('%02d:%02d', $this->hour, $this->minute);
    }

    /** Format Indonesia HH.MM untuk pesan. */
    public function label(): string
    {
        return sprintf('%02d.%02d', $this->hour, $this->minute);
    }
}
