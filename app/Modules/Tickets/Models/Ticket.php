<?php

namespace App\Modules\Tickets\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Ein Ticket: eine Anfrage oder Störungsmeldung einer angemeldeten Person, die Bearbeitende klären. */
class Ticket extends Model
{
    public const STATUSES = ['open' => 'Offen', 'in_progress' => 'In Bearbeitung', 'waiting' => 'Wartet auf Rückmeldung', 'resolved' => 'Gelöst', 'closed' => 'Geschlossen'];

    public const PRIORITIES = ['low' => 'Niedrig', 'normal' => 'Normal', 'high' => 'Hoch'];

    public const CATEGORIES = ['question' => 'Frage', 'problem' => 'Problem oder Fehler', 'account' => 'Konto und Zugang', 'other' => 'Sonstiges'];

    protected $fillable = ['subject', 'category', 'status', 'priority', 'requester_id', 'assignee_id', 'closed_at', 'last_activity_at'];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime', 'last_activity_at' => 'datetime'];
    }

    public function reference(): string
    {
        return 'T-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return HasMany<TicketMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('id');
    }

    /** @return HasMany<TicketAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class)->orderBy('id');
    }

    /** @return HasMany<TicketEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->orderBy('id');
    }
}
