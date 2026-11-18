<?php

namespace App\Modules\Seminars\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ein Termin eines Seminars (bei Reihen mehrere, auch nicht aufeinanderfolgende Tage). */
class SeminarSession extends Model
{
    protected $fillable = ['seminar_id', 'starts_at', 'ends_at', 'location'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }
}
