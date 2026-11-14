<?php

namespace App\Modules\Faq\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eine Frage mit Antwort in einer FAQ-Kategorie. */
class FaqEntry extends Model
{
    protected $fillable = ['faq_category_id', 'question', 'answer', 'sort_order'];

    /** @return BelongsTo<FaqCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }
}
