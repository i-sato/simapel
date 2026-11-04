<?php

declare(strict_types=1);

namespace App\Domain\Audit;

/**
 * Catatan peristiwa yang wajib dapat ditelusuri (TOR bagian 5: keterlacakan).
 *
 * Konteks hanya boleh berisi nilai skalar dan tidak boleh memuat kunci rahasia
 * maupun identitas mentah; gunakan nilai tersamar (mis. nip_masked).
 */
final readonly class AuditEvent
{
    private const FORBIDDEN_CONTEXT_KEYS = [
        'password', 'password_confirmation', 'current_password', 'token', 'secret', 'nip', 'nik',
    ];

    /** @var array<string, scalar|null> */
    public array $context;

    /**
     * Konteks diterima sebagai mixed lalu divalidasi, karena dapat berasal dari data dinamis.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public AuditEventType $type,
        public string $description,
        public ?int $actorId = null,
        public ?string $subjectType = null,
        public ?int $subjectId = null,
        array $context = [],
    ) {
        $validated = [];

        foreach ($context as $key => $value) {
            if (in_array(strtolower($key), self::FORBIDDEN_CONTEXT_KEYS, true)) {
                throw SensitiveAuditContext::forKey($key);
            }

            if (! is_scalar($value) && $value !== null) {
                throw SensitiveAuditContext::nonScalar($key);
            }

            $validated[$key] = $value;
        }

        $this->context = $validated;
    }
}
