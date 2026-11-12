<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Partners\Models\Partner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Seminars. Ein Kostenvoranschlag für eine Schule: mehrere Positionen (Produkt mit Stückzahl, Eingaben, Posten, Summe) mit dem Preisstand der aktuellen Fassung. Änderungen erzeugen eine neue Fassung; alle Fassungen stehen unveränderlich in seminar_quote_versions. */
class SeminarQuote extends Model
{
    protected $fillable = ['partner_id', 'seminar_id', 'price_list_id', 'version', 'positions', 'total_cents', 'created_by_user_id'];

    protected function casts(): array
    {
        return ['positions' => 'array', 'total_cents' => 'integer', 'version' => 'integer'];
    }

    /** @return HasMany<SeminarQuoteVersion, $this> alle Fassungen, die neueste zuerst */
    public function versions(): HasMany
    {
        return $this->hasMany(SeminarQuoteVersion::class, 'quote_id')->orderByDesc('version');
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
