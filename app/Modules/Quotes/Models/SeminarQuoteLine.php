<?php

namespace App\Modules\Quotes\Models;

use App\Modules\Quotes\Support\QuoteCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Position eines Kostenvoranschlags. */
class SeminarQuoteLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['seminar_quote_id', 'position', 'description', 'quantity', 'unit', 'unit_price_cents'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_price_cents' => 'integer', 'position' => 'integer'];
    }

    /** @return BelongsTo<SeminarQuote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(SeminarQuote::class, 'seminar_quote_id');
    }

    public function total(): int
    {
        return QuoteCalculator::lineTotal($this->quantity, $this->unit_price_cents);
    }
}
