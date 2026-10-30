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

    protected $fillable = ['slug', 'title', 'teaser', 'body', 'category', 'image_path', 'image_alt', 'image_credit', 'status', 'published_at', 'featured', 'created_by_user_id', 'updated_by_user_id'];

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
        return filled($this->teaser) ? (string) $this->teaser : Str::limit(trim(strip_tags(Str::markdown($this->body, ['html_input' => 'strip']))), $limit);
    }

    /** Text als sicheres HTML (einfaches Markdown, kein eigenes HTML, keine unsicheren Links). */
    public function html(): string
    {
        return Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
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
