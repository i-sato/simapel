<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Shared\Contracts\Clock;
use App\Domain\Audit\AuditEvent;
use App\Models\AuditLog;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class EloquentAuditTrail implements AuditTrail
{
    public function __construct(
        private Container $container,
        private Clock $clock,
    ) {}

    public function record(AuditEvent $event): void
    {
        /** @var Request $request */
        $request = $this->container->make('request');
        $isHttp = $request->route() !== null;

        AuditLog::query()->create([
            'occurred_at' => $this->clock->now(),
            'event_type' => $event->type,
            'actor_user_id' => $event->actorId,
            'subject_type' => $event->subjectType,
            'subject_id' => $event->subjectId,
            'description' => Str::limit($event->description, 500, ''),
            'context' => $event->context === [] ? null : $event->context,
            'ip_address' => $isHttp ? $request->ip() : null,
            'user_agent' => $isHttp ? Str::limit((string) $request->userAgent(), 255, '') : null,
            'channel' => $isHttp ? 'web' : 'cli',
        ]);
    }
}
