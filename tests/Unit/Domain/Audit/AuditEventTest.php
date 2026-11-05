<?php

namespace Tests\Unit\Domain\Audit;

use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Audit\SensitiveAuditContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuditEventTest extends TestCase
{
    public function test_accepts_scalar_context(): void
    {
        $event = new AuditEvent(
            type: AuditEventType::LoginFailed,
            description: 'Login gagal.',
            context: ['nip_masked' => '9999**********0001', 'attempt' => 2, 'flag' => true, 'note' => null],
        );

        $this->assertSame('9999**********0001', $event->context['nip_masked']);
        $this->assertNull($event->actorId);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function forbiddenKeys(): iterable
    {
        foreach (['password', 'current_password', 'password_confirmation', 'token', 'secret', 'nip', 'NIP', 'nik'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('forbiddenKeys')]
    public function test_rejects_sensitive_context_keys(string $key): void
    {
        $this->expectException(SensitiveAuditContext::class);

        new AuditEvent(AuditEventType::LoginFailed, 'Login gagal.', context: [$key => 'rahasia']);
    }

    public function test_rejects_non_scalar_context_values(): void
    {
        $this->expectException(SensitiveAuditContext::class);

        new AuditEvent(AuditEventType::UserCreated, 'Akun dibuat.', context: ['payload' => ['nested' => 'data']]);
    }

    public function test_every_event_type_has_a_label(): void
    {
        foreach (AuditEventType::cases() as $type) {
            $this->assertNotSame('', $type->label());
        }
    }
}
