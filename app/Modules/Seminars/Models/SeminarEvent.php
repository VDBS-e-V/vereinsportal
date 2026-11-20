<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\SeminarStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eintrag im Verlauf eines Seminars: Anfrage, Statuswechsel mit Begründung, später Mails und Uploads. */
class SeminarEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['seminar_id', 'user_id', 'actor_label', 'type', 'from_status', 'to_status', 'comment', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Text für den Verlauf, z. B. „Anfrage → In Prüfung“. */
    public function describe(): string
    {
        return match ($this->type) {
            'requested' => 'Anfrage gestellt',
            'status' => (SeminarStatus::tryFrom((string) $this->from_status)?->label() ?? '–').' → '.(SeminarStatus::tryFrom((string) $this->to_status)?->label() ?? '–'),
            default => (string) $this->type,
        };
    }
}
