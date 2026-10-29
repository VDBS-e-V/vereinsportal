<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Ein Kostenvoranschlag (unveränderlich): Eingaben, Posten und Summe mit dem Preisstand von damals. */
class SeminarQuote extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['seminar_id', 'version', 'price_list_id', 'inputs', 'lines', 'total_cents', 'item_title', 'item_content', 'created_by_user_id'];

    protected function casts(): array
    {
        return ['inputs' => 'array', 'lines' => 'array', 'total_cents' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<SeminarPriceList, $this> */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(SeminarPriceList::class, 'price_list_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
