<?php

namespace App\Modules\ContactForms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Ein Kontaktformular: Empfänger, Art (Nachricht oder nur Angaben), abgefragte Personendaten und eigene Felder. Im Portal pflegbar. */
class ContactForm extends Model
{
    public const KINDS = ['message' => 'Nachricht (Betreff und Text)', 'data' => 'Nur Angaben erfassen (ohne Nachricht)'];

    public const PERSON_MODES = ['no' => 'nicht abfragen', 'optional' => 'freiwillig', 'required' => 'Pflichtangabe'];

    protected $fillable = ['slug', 'name', 'description', 'kind', 'recipient_email', 'recipient_label', 'ask_phone', 'ask_organization', 'fields', 'success_text', 'send_copy', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['fields' => 'array', 'send_copy' => 'boolean', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    /** @return HasMany<ContactTopic, $this> */
    public function topics(): HasMany
    {
        return $this->hasMany(ContactTopic::class);
    }

    /** @param Builder<ContactForm> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Das Standard-Kontaktformular (für „Mein Thema ist nicht dabei“ und Rückfragen aus FAQ-Seiten). */
    public static function standard(): ?self
    {
        return static::query()->where('is_default', true)->where('is_active', true)->first();
    }

    /** Ob nach den Angaben zur Person (Schritt 2) noch eine eigene Seite folgt: Anfrage schreiben oder weitere Angaben. */
    public function hasRequestStep(): bool
    {
        return $this->isMessage() || $this->customFields() !== [];
    }

    public function isMessage(): bool
    {
        return $this->kind !== 'data';
    }

    /** @return list<array{key: string, label: string, type: string, required: bool, options: list<string>, hint: ?string}> */
    public function customFields(): array
    {
        return array_values(array_filter((array) $this->fields, fn ($field): bool => is_array($field) && filled($field['key'] ?? null) && filled($field['label'] ?? null)));
    }
}
