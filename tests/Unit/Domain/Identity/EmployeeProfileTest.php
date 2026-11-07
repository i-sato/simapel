<?php

namespace Tests\Unit\Domain\Identity;

use App\Domain\Identity\EmployeeProfile;
use App\Domain\Identity\InvalidEmployeeProfile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmployeeProfileTest extends TestCase
{
    public function test_normalizes_whitespace_and_email(): void
    {
        $profile = EmployeeProfile::fromInput('  Ratna Uji ', ' Ratna.Uji@Example.TEST ', 'Penata Muda  -   III/a', ' Pranata Komputer ');

        $this->assertSame('Ratna Uji', $profile->name);
        $this->assertSame('ratna.uji@example.test', $profile->email);
        $this->assertSame('Penata Muda - III/a', $profile->pangkatGolongan);
        $this->assertSame('Pranata Komputer', $profile->jabatan);
    }

    public function test_optional_fields_may_be_empty(): void
    {
        $profile = EmployeeProfile::fromInput('Ratna Uji', '', ' ', null);

        $this->assertNull($profile->email);
        $this->assertNull($profile->pangkatGolongan);
        $this->assertNull($profile->jabatan);
    }

    /**
     * @return iterable<string, array{string, ?string, ?string, ?string, string}>
     */
    public static function invalidProfiles(): iterable
    {
        yield 'nama kosong' => ['  ', null, null, null, 'name'];
        yield 'nama terlalu panjang' => [str_repeat('a', 256), null, null, null, 'name'];
        yield 'email tidak valid' => ['Ratna', 'bukan-email', null, null, 'email'];
        yield 'pangkat terlalu panjang' => ['Ratna', null, str_repeat('a', 101), null, 'pangkat_golongan'];
        yield 'jabatan terlalu panjang' => ['Ratna', null, null, str_repeat('a', 151), 'jabatan'];
    }

    #[DataProvider('invalidProfiles')]
    public function test_rejects_invalid_values_on_their_field(string $name, ?string $email, ?string $pangkat, ?string $jabatan, string $field): void
    {
        try {
            EmployeeProfile::fromInput($name, $email, $pangkat, $jabatan);
            $this->fail('InvalidEmployeeProfile seharusnya dilempar.');
        } catch (InvalidEmployeeProfile $exception) {
            $this->assertSame($field, $exception->field());
        }
    }
}
