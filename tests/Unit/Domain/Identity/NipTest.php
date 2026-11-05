<?php

namespace Tests\Unit\Domain\Identity;

use App\Domain\Identity\InvalidNip;
use App\Domain\Identity\Nip;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NipTest extends TestCase
{
    public function test_accepts_exactly_eighteen_digits_and_trims_surrounding_whitespace(): void
    {
        $nip = Nip::fromString("  999900000000000001\n");

        $this->assertSame('999900000000000001', $nip->value);
        $this->assertSame('999900000000000001', (string) $nip);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidValues(): iterable
    {
        yield 'kosong' => [''];
        yield '17 digit' => ['99990000000000001'];
        yield '19 digit' => ['9999000000000000011'];
        yield 'mengandung huruf' => ['99990000000000000A'];
        yield 'spasi di tengah' => ['999900000 00000001'];
        yield 'alamat email' => ['admin@simapel.local'];
        yield 'angka unicode' => ['٩٩٩٩٠٠٠٠٠٠٠٠٠٠٠٠٠١'];
    }

    #[DataProvider('invalidValues')]
    public function test_rejects_invalid_values(string $value): void
    {
        $this->assertFalse(Nip::isValid($value));

        $this->expectException(InvalidNip::class);
        Nip::fromString($value);
    }

    public function test_invalid_nip_points_to_nip_field(): void
    {
        $this->assertSame('nip', (new InvalidNip)->field());
    }

    public function test_masks_middle_digits(): void
    {
        $masked = Nip::fromString('198111192008031001')->masked();

        $this->assertSame('1981**********1001', $masked);
        $this->assertSame(18, strlen($masked));
    }

    public function test_equality_compares_value(): void
    {
        $nip = Nip::fromString('999900000000000001');

        $this->assertTrue($nip->equals(Nip::fromString('999900000000000001')));
        $this->assertFalse($nip->equals(Nip::fromString('999900000000000002')));
    }
}
