<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Interesse einer teamenden Person an einem ausgeschriebenen Seminar, mit Verfügbarkeit. */
class SeminarApplication extends Model
{
    public const APPLIED = 'applied';

    public const WITHDRAWN = 'withdrawn';

    protected $fillable = ['seminar_id', 'user_id', 'availability', 'status'];

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
