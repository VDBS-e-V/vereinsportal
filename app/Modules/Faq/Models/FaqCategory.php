<?php

namespace App\Modules\Faq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Eine Gruppe von Fragen auf der FAQ-Seite (z. B. „Zugang beantragen“). Im Portal pflegbar. */
class FaqCategory extends Model
{
    protected $fillable = ['title', 'description', 'sort_order', 'in_faq_page', 'is_active'];

    protected function casts(): array
    {
        return ['in_faq_page' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @return HasMany<FaqEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(FaqEntry::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @param Builder<FaqCategory> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
