<?php

namespace App\Modules\Articles\Models;

use App\Modules\Access\Models\User;
use App\Modules\Access\Models\UserProfile;
use App\Support\Html\RichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $fillable = ['slug', 'title', 'subtitle', 'teaser', 'body', 'category', 'image_path', 'image_alt', 'image_credit', 'tags', 'show_author', 'author_name', 'author_box', 'author_text', 'author_image_path', 'author_image_credit', 'more_info', 'status', 'published_at', 'featured', 'created_by_user_id', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'featured' => 'boolean', 'tags' => 'array', 'show_author' => 'boolean', 'author_box' => 'boolean'];
    }

    /** Veröffentlicht und nicht in der Zukunft datiert. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)->where('published_at', '<=', now());
    }

    /** Suche in Titel, Untertitel, Anreißer und Schlagwörtern. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(fn (Builder $q) => $q->where('title', 'like', $like)->orWhere('subtitle', 'like', $like)->orWhere('teaser', 'like', $like)->orWhere('tags', 'like', $like));
    }

    /** Artikel mit mindestens einem der Schlagwörter. @param list<string> $tags */
    public function scopeWithAnyTag(Builder $query, array $tags): Builder
    {
        if ($tags === []) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($tags): void {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /** Schlagwörter aus der Adresse (?schlagwort=a oder ?schlagwort[]=a&schlagwort[]=b), bereinigt. @return list<string> */
    public static function tagsFromQuery(mixed $value): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($t) => is_string($t) ? trim($t) : '', (array) $value), fn (string $t) => $t !== '')));
    }

    /** Kategorien, die vorkommen (nur sichtbare Artikel oder alle). @return list<string> */
    public static function categoriesInUse(bool $visibleOnly = true): array
    {
        $query = $visibleOnly ? self::query()->visible() : self::query();

        return $query->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all();
    }

    /** Schlagwörter, die vorkommen, alphabetisch. @return list<string> */
    public static function tagsInUse(bool $visibleOnly = true): array
    {
        $query = $visibleOnly ? self::query()->visible() : self::query();

        return $query->whereNotNull('tags')->pluck('tags')->flatten()->filter(fn ($t) => is_string($t) && $t !== '')->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }

    /** Jahre der Veröffentlichung, neueste zuerst. @return list<int> */
    public static function yearsInUse(bool $visibleOnly = true): array
    {
        $query = $visibleOnly ? self::query()->visible() : self::query()->whereNotNull('published_at');

        return $query->pluck('published_at')->map(fn ($d) => (int) $d->format('Y'))->unique()->sortDesc()->values()->all();
    }

    public function isVisible(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at !== null && $this->published_at->lte(now());
    }

    /** Text als Anreißer: eigener Anreißer, sonst der Anfang des Textes. */
    public function excerpt(int $limit = 180): string
    {
        return filled($this->teaser) ? (string) $this->teaser : RichText::toPlain((string) $this->body, $limit);
    }

    /** Text als sicheres HTML (einfaches Markdown, kein eigenes HTML, keine unsicheren Links). */
    public function html(): string
    {
        return RichText::render($this->body);
    }

    /** Autorenprofil des anlegenden Kontos (Angaben unter Konto › Profil), falls vorhanden. */
    private function authorProfile(): ?UserProfile
    {
        return $this->createdBy?->profile;
    }

    /** Name der Autorin oder des Autors: Angabe am Artikel, sonst Profil des Kontos, sonst die Person. */
    public function authorName(): ?string
    {
        $name = filled($this->author_name) ? $this->author_name : ($this->authorProfile()?->author_name ?: $this->createdBy?->person?->fullName());

        return filled($name) ? (string) $name : null;
    }

    /** Pfad des Autorenfotos: eigenes Foto am Artikel, sonst das Profilfoto, wenn das Profil es dafür freigibt. */
    public function authorImagePath(): ?string
    {
        if (filled($this->author_image_path)) {
            return $this->author_image_path;
        }

        $profile = $this->authorProfile();

        return $profile?->author_use_photo && filled($this->createdBy?->avatar_path) ? $this->createdBy->avatar_path : null;
    }

    public function authorImageCredit(): ?string
    {
        if (filled($this->author_image_path)) {
            return filled($this->author_image_credit) ? $this->author_image_credit : null;
        }

        return $this->authorImagePath() !== null ? ($this->authorProfile()?->author_photo_credit ?: 'Privat') : null;
    }

    /** @return list<string> */
    public function tagList(): array
    {
        return array_values(array_filter((array) $this->tags, 'is_string'));
    }

    public function authorTextHtml(): string
    {
        return RichText::render(filled($this->author_text) ? $this->author_text : $this->authorProfile()?->author_text);
    }

    public function moreInfoHtml(): string
    {
        return RichText::render($this->more_info);
    }

    /** Wurde der Artikel nach der Veröffentlichung noch geändert? (Toleranz: eine Stunde, damit das Anlegen nicht zählt) */
    public function editedAfterPublishing(): bool
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
