<?php

namespace App\Modules\Articles\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Modul: Articles. Ein Artikel für die öffentlichen Seiten (Startseite und Artikelliste). */
class Article extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    /** Auswahl der Kategorien im Formular (bestehende andere Werte bleiben erhalten). */
    public const CATEGORIES = ['Themenartikel', 'Neuigkeiten', 'Veranstaltungen', 'Bildungsarbeit', 'Verein', 'Presse', 'Materialien'];

    /** @return array<string, string> */
    public static function categoryOptions(?string $current = null): array
    {
        $list = self::CATEGORIES;

        if (filled($current) && ! in_array($current, $list, true)) {
            $list[] = $current;
        }

        return array_combine($list, $list);
    }

    protected $fillable = ['slug', 'title', 'subtitle', 'teaser', 'body', 'category', 'image_path', 'image_alt', 'image_credit', 'status', 'published_at', 'featured', 'created_by_user_id', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'featured' => 'boolean'];
    }

    /** Veröffentlicht und nicht in der Zukunft datiert. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)->where('published_at', '<=', now());
    }

    public function isVisible(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at !== null && $this->published_at->lte(now());
    }

    /** Text als Anreißer: eigener Anreißer, sonst der Anfang des Textes. */
    public function excerpt(int $limit = 180): string
    {
        return filled($this->teaser) ? (string) $this->teaser : \App\Support\Html\RichText::toPlain((string) $this->body, $limit);
    }

    /** Text als sicheres HTML (einfaches Markdown, kein eigenes HTML, keine unsicheren Links). */
    public function html(): string
    {
        return \App\Support\Html\RichText::render($this->body);
    }

    /** Name der Autorin oder des Autors (Person des anlegenden Kontos), sonst nichts. */
    public function authorName(): ?string
    {
        $name = $this->createdBy?->person?->fullName();

        return filled($name) ? $name : null;
    }

    /** Wurde der Artikel nach der Veröffentlichung noch geändert? (Toleranz: eine Stunde, damit das Anlegen nicht zählt) */
    public function wasChanged(): bool
    {
        return $this->published_at !== null && $this->updated_at !== null && $this->updated_at->gt($this->published_at->copy()->addHour());
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
