<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Globaler Titel und Standardbeschreibung eines Produkts, mit Platzhaltern (QuoteTexts::PLACEHOLDERS). */
class SeminarProductText extends Model
{
    protected $fillable = ['product', 'title', 'content', 'updated_by_user_id'];

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
