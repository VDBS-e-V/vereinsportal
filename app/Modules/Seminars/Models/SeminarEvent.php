<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\SeminarEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Ein Eintrag im Verlauf eines Seminars (nur anlegen). */
class SeminarEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['seminar_id', 'type', 'occurred_at', 'actor_user_id', 'actor_label', 'comment', 'data'];

    protected function casts(): array
    {
        return ['type' => SeminarEventType::class, 'occurred_at' => 'datetime', 'data' => 'array'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
