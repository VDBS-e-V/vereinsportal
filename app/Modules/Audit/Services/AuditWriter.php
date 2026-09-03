<?php

namespace App\Modules\Audit\Services;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class AuditWriter
{
    public function write(
        string $eventKey,
        AuditActorType $actorType,
        ?int $actorUserId = null,
        ?string $actorContext = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $comment = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
        ?CarbonInterface $occurredAt = null,
    ): AuditEvent {
        $occurredAt ??= now();

        return AuditEvent::query()->create([
            'occurred_at' => $occurredAt,
            'event_key' => $eventKey,
            'actor_type' => $actorType,
            'actor_user_id' => $actorUserId,
            'actor_context' => $actorContext,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'old_values' => AuditEventCatalog::filterValues(
                $eventKey,
                $oldValues,
            ),
            'new_values' => AuditEventCatalog::filterValues(
                $eventKey,
                $newValues,
            ),
            'comment' => $comment,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_info' => $deviceInfo,
            'retention_until' => CarbonImmutable::instance(
                $occurredAt
            )->addYears(3),
        ]);
    }
}