<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Events. Ein Tag/Termin einer Großveranstaltung (für die Anwesenheitsliste). */
class EventSession extends Model
{
    protected $fillable = ['event_id', 'starts_at', 'ends_at', 'location', 'notes'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<EventAttendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class, 'session_id');
    }

    /** Zeitraum für die Anzeige, z. B. „03.12.2026, 09:00–15:30 Uhr“. */
    public function period(): string
    {
        $same = $this->starts_at->isSameDay($this->ends_at);

        return $this->starts_at->format('d.m.Y, H:i').($same ? '–'.$this->ends_at->format('H:i') : ' – '.$this->ends_at->format('d.m.Y, H:i')).' Uhr';
    }
}
