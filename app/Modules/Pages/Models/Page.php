<?php

namespace App\Modules\Pages\Models;

use App\Modules\Access\Models\User;
use App\Modules\Pages\Enums\PageKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Modul: Pages. Bearbeiteter Text einer Rechtstext-Seite (Impressum, Datenschutz, …). Ohne Zeile gilt der Standardtext. */
class Page extends Model
{
    protected $fillable = ['key', 'body', 'updated_by_user_id'];

    /** Die gespeicherte Seite oder eine ungespeicherte mit dem Standardtext. */
    public static function for(PageKey $key): self
    {
        return self::query()->where('key', $key->value)->first() ?? new self(['key' => $key->value, 'body' => $key->defaultBody()]);
    }

    public function pageKey(): PageKey
    {
        return PageKey::from($this->key);
    }

    public function isDefault(): bool
    {
        return ! $this->exists;
    }

    /** Text als sicheres HTML (einfaches Markdown, kein eigenes HTML, keine unsicheren Links). */
    public function html(): string
    {
        return Str::markdown((string) $this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
