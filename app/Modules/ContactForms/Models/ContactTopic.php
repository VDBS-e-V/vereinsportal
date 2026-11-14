<?php

namespace App\Modules\ContactForms\Models;

use App\Modules\Faq\Models\FaqCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Ein Thema der Themenwahl im Kontaktbereich: Bereich (Kategorie) oder ein Ziel (Formular, FAQ-Seite, Standardformular, Link). */
class ContactTopic extends Model
{
    public const ACTIONS = [
        'category' => 'Bereich (fasst Themen zusammen)',
        'form' => 'Führt zu einem Formular',
        'default' => 'Führt zum Standard-Kontaktformular',
        'faq' => 'Führt zu einer FAQ-Seite',
        'link' => 'Führt zu einer anderen Seite (Link)',
    ];

    protected $fillable = ['parent_id', 'title', 'description', 'keywords', 'action', 'contact_form_id', 'faq_category_id', 'target_url', 'faq_offer_form', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['faq_offer_form' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @return BelongsTo<ContactTopic, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<ContactTopic, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('title');
    }

    /** @return BelongsTo<ContactForm, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(ContactForm::class, 'contact_form_id');
    }

    /** @return BelongsTo<FaqCategory, $this> Die FAQ-Kategorie, auf die ein FAQ-Thema verweist (gepflegt unter Geschäftsstelle › FAQ). */
    public function faqCategory(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    /** @param Builder<ContactTopic> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isCategory(): bool
    {
        return $this->action === 'category';
    }

    /** Das Formular, zu dem das Thema führt (eigenes oder das Standardformular), nur wenn es aktiv ist. */
    public function targetForm(): ?ContactForm
    {
        $form = $this->action === 'default' ? ContactForm::standard() : $this->form;

        return $form !== null && $form->is_active ? $form : null;
    }

    /** Ob das Thema zurzeit ein erreichbares Ziel hat (sonst bleibt es in der Themenwahl verborgen). */
    public function isReachable(): bool
    {
        return match ($this->action) {
            'form', 'default' => $this->targetForm() !== null,
            'faq' => ($this->faqCategory !== null && $this->faqCategory->is_active && $this->faqCategory->entries->isNotEmpty()) || ($this->faq_offer_form && ($this->targetForm() ?? ContactForm::standard()) !== null),
            'link' => filled($this->target_url),
            default => false,
        };
    }
}
