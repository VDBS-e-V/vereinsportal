<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Abschluss eines Seminars mit Checkliste und Schnappschuss der Kennzahlen (nur Zahlen, keine Personendaten). Wieder geöffnete Abschlüsse bleiben als Version erhalten. */
class SeminarClosure extends Model
{
    protected $fillable = ['seminar_id', 'closed_by_user_id', 'closed_at', 'checklist', 'snapshot', 'reopened_at', 'reopened_by_user_id', 'reopen_reason'];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime', 'reopened_at' => 'datetime', 'checklist' => 'array', 'snapshot' => 'array'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<User, $this> */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}
