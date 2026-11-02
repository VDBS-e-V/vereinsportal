<?php

namespace App\Modules\Mailings\Models;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Mailings. Ein versendetes Schreiben (Betreff und Text mit Platzhaltern, Zahl der Empfänger), nie die Empfängerliste. */
class Mailing extends Model
{
    public const KIND_MEMBERS = 'members';

    public const KIND_TEAM = 'team';

    public const KIND_GENERAL = 'general';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending_approval';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENDING = 'sending';

    public const STATUS_DONE = 'done';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    protected $fillable = ['kind', 'subject', 'body', 'own_text', 'seminar_id', 'audience', 'groups', 'recipients_count', 'sent_count', 'failed_count', 'sent_by_user_id', 'status', 'queued_at', 'started_at', 'finished_at', 'cancelled_at', 'values', 'mandatory', 'send_at', 'approved_by_user_id', 'approved_at', 'payload', 'rejection_note', 'sender_area'];

    protected function casts(): array
    {
        return ['groups' => 'array', 'values' => 'array', 'payload' => 'array', 'mandatory' => 'boolean', 'send_at' => 'datetime', 'approved_at' => 'datetime', 'queued_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    /** @return HasMany<MailingDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(MailingDelivery::class);
    }

    /** Noch in Arbeit: wartet auf den Worker oder wird gerade gesendet. */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_SENDING], true);
    }

    /** Noch nicht versendet: Entwurf, wartet auf Freigabe oder ist geplant. */
    public function isPreparation(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_SCHEDULED], true);
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Entwurf',
            self::STATUS_PENDING => 'Wartet auf Freigabe',
            self::STATUS_SCHEDULED => 'Geplant',
            self::STATUS_QUEUED => 'Wartet auf den Versand',
            self::STATUS_SENDING => 'Wird gesendet',
            self::STATUS_CANCELLED => 'Abgebrochen',
            self::STATUS_FAILED => 'Fehlgeschlagen',
            default => 'Gesendet',
        };
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            self::STATUS_QUEUED, self::STATUS_SENDING, self::STATUS_PENDING => 'warning',
            self::STATUS_DRAFT, self::STATUS_SCHEDULED => 'neutral',
            self::STATUS_CANCELLED => 'neutral',
            self::STATUS_FAILED => 'error',
            default => 'success',
        };
    }

    /** Gruppen, die ausgenommen werden (Auswahl „außer“). @return list<string> */
    public function excludedGroups(): array
    {
        return array_values(array_map('strval', (array) (($this->payload ?? [])['exclude'] ?? [])));
    }

    /** Einzeln gewählte Personen (Marken). @return list<string> */
    public function chosenPeople(): array
    {
        return array_values(array_map('strval', (array) (($this->payload ?? [])['people'] ?? [])));
    }

    /** Dürfen die Adressen der fehlgeschlagenen Zustellungen gezeigt werden? Wer das Schreiben gesendet hat und wer die Gruppe anschreiben darf. */
    public function addressesVisibleTo(User $user): bool
    {
        return $user->id === $this->sent_by_user_id
            || $user->can('mailings.members')
            || ($this->kind === self::KIND_TEAM && $user->can('mailings.team'));
    }
}
