<?php

namespace App\Modules\Quotes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Eine Leistung der Preisliste (zum Übernehmen in einen Kostenvoranschlag). */
class QuotePriceItem extends Model
{
    protected $fillable = ['name', 'unit', 'unit_price_cents', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['unit_price_cents' => 'integer', 'active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @param  Builder<QuotePriceItem>  $query
     * @return Builder<QuotePriceItem>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
