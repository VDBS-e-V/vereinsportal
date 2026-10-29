<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Eine Fassung eines Kostenvoranschlags (Schnappschuss, nur anlegen). */
class SeminarQuoteVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['quote_id', 'version', 'price_list_id', 'positions', 'total_cents', 'comment', 'created_by_user_id'];

    protected function casts(): array
    {
        return ['positions' => 'array', 'total_cents' => 'integer', 'version' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
    }

    /** @return BelongsTo<SeminarQuote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(SeminarQuote::class, 'quote_id');
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
