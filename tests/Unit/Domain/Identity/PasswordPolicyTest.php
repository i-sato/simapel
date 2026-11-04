<?php

namespace Tests\Unit\Domain\Identity;

use App\Domain\Identity\PasswordPolicy;
use App\Domain\Identity\WeakPassword;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    public function test_strong_password_has_no_violations(): void
    {
        $this->assertSame([], (new PasswordPolicy)->violations('Pelatihan-2026!'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function weakPasswords(): iterable
    {
        yield 'terlalu pendek' => ['Ab1!pendek', 'minimal 12 karakter'];
        yield 'tanpa huruf besar' => ['pelatihan-2026!', 'huruf besar dan huruf kecil'];
        yield 'tanpa huruf kecil' => ['PELATIHAN-2026!', 'huruf besar dan huruf kecil'];
        yield 'tanpa angka' => ['Pelatihan-Digital!', 'angka'];
        yield 'tanpa simbol' => ['Pelatihan2026Aman', 'simbol'];
        yield 'melebihi batas bcrypt' => [str_repeat('Aa1!', 19), 'maksimal 72'];
    }

    #[DataProvider('weakPasswords')]
    public function test_reports_each_violation(string $password, string $expectedFragment): void
    {
        $violations = (new PasswordPolicy)->violations($password);

        $this->assertNotSame([], $violations);
        $this->assertStringContainsString($expectedFragment, implode(' ', $violations));
    }

    public function test_assert_throws_weak_password_on_password_field(): void
    {
        try {
            (new PasswordPolicy)->assertSatisfiedBy('lemah');
            $this->fail('WeakPassword seharusnya dilempar.');
        } catch (WeakPassword $exception) {
            $this->assertSame('password', $exception->field());
        }
    }

    public function test_minimum_length_is_configurable(): void
    {
        $policy = new PasswordPolicy(minLength: 16);

        $this->assertNotSame([], $policy->violations('Pelatihan-2026!'));
        $this->assertSame('Minimal 16 karakter', $policy->summary()[0]);
    }
}
