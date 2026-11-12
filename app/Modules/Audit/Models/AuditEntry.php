<?php

namespace App\Modules\Audit\Models;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Modul: Audit. Ein Eintrag im Protokoll. Einträge werden nur angelegt, nie geändert oder einzeln gelöscht;
 * Aufbewahrungsfristen setzt der Befehl audit:prune durch.
 *
 * @property-read AuditEvent|null $eventType
 */
class AuditEntry extends Model
{
    public $timestamps = false;

    protected $table = 'audit_entries';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(static fn () => throw new LogicException('Protokolleinträge sind unveränderlich.'));
        static::deleting(static fn () => throw new LogicException('Protokolleinträge werden nicht einzeln gelöscht.'));
    }

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'context' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function getEventTypeAttribute(): ?AuditEvent
    {
        return AuditEvent::tryFrom((string) $this->event);
    }
}
