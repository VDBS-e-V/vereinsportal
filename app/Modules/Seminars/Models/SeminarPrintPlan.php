<?php

namespace App\Modules\Seminars\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Gespeicherter Druckplan eines Seminars: je Dokument Seiten, Exemplare, Farbe, Seiten pro Blatt, doppelseitig. */
class SeminarPrintPlan extends Model
{
    protected $fillable = ['seminar_id', 'items', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }
}
