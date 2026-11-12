<?php

namespace App\Modules\Seminars\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Ein Termin (Einheit) eines Seminars. */
class SeminarSession extends Model
{
    protected $fillable = ['seminar_id', 'starts_at', 'ends_at', 'location', 'notes'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** Zeitraum für die Anzeige, z. B. „03.12.2026, 09:00–15:30 Uhr“. */
    public function period(): string
    {
        $same = $this->starts_at->isSameDay($this->ends_at);

        return $this->starts_at->format('d.m.Y, H:i').($same ? '–'.$this->ends_at->format('H:i') : ' – '.$this->ends_at->format('d.m.Y, H:i')).' Uhr';
    }
}
