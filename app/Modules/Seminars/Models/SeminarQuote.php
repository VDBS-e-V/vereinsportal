<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Partners\Models\Partner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Ein Kostenvoranschlag (unveränderlich) für eine Schule: mehrere Positionen (Produkt mit Stückzahl, Eingaben, Posten, Summe) mit dem Preisstand von damals. */
class SeminarQuote extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['partner_id', 'seminar_id', 'price_list_id', 'positions', 'total_cents', 'created_by_user_id'];

    protected function casts(): array
    {
        return ['positions' => 'array', 'total_cents' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
    }

    /** @return BelongsTo<Partner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
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
