<?php

namespace App\Modules\Membership\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Membership\Enums\MembershipEventType;
use App\Modules\Membership\Models\Membership;

/** Schreibt den Verlauf einer Mitgliedschaft (membership_events). Bewusst getrennt vom allgemeinen Protokoll. */
final class MembershipHistory
{
    /**
     * @param  array<string, mixed>  $data
     * @param  string|null  $actorLabel  Bezeichnung ohne angemeldeten Akteur, z. B. „Online-Antrag“
     */
    public function record(Membership $membership, MembershipEventType $type, ?User $by = null, ?string $comment = null, array $data = [], ?string $actorLabel = null): void
    {
        $membership->events()->create([
            'type' => $type,
            'occurred_at' => now(),
            'actor_user_id' => $by?->id,
            'actor_label' => $by !== null ? AuditLog::label($by) : $actorLabel,
            'comment' => filled($comment) ? $comment : null,
            'data' => $data === [] ? null : $data,
        ]);
    }
}
