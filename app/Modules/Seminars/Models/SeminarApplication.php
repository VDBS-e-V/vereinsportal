<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Bewerbung eines Teamenden-Kontos auf ein Seminar. */
class SeminarApplication extends Model
{
    protected $fillable = ['seminar_id', 'user_id', 'status', 'note', 'decided_by_user_id', 'decided_at'];

    protected function casts(): array
    {
        return ['status' => ApplicationStatus::class, 'decided_at' => 'datetime'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
