<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Events. Anmeldung einer Schülerin oder eines Schülers (selbst oder per Liste durch die Koordination). Bestätigt, sobald verified_at gesetzt ist. */
class EventRegistration extends Model
{
    public const SOURCE_SELF = 'self';

    public const SOURCE_UPLOAD = 'upload';

    protected $fillable = ['event_id', 'name', 'school', 'class_name', 'birth_date', 'email', 'ticket_code', 'source', 'verified_at', 'uploaded_by_user_id'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'verified_at' => 'datetime'];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<EventAttendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class, 'registration_id');
    }

    public function isConfirmed(): bool
    {
        return $this->verified_at !== null;
    }

    /** Ticketcode zur Anzeige, z. B. „K7QM-4XWD“. */
    public function ticket(): string
    {
        return substr($this->ticket_code, 0, 4).'-'.substr($this->ticket_code, 4);
    }
}
