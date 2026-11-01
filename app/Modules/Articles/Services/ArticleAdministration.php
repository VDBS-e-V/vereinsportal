<?php

namespace App\Modules\Articles\Services;

use App\Modules\Access\Models\User;
use App\Modules\Articles\Models\Article;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Artikel anlegen, ändern und löschen. Bilder liegen im privaten Speicher und werden über eine Route ausgeliefert (kein storage:link nötig). */
final class ArticleAdministration
{
    public const DISK = 'local';

    /**
     * @param  array<string, mixed>  $data  validierte Formulardaten
     */
    public function create(array $data, ?UploadedFile $image, User $by, ?UploadedFile $authorImage = null): Article
    {
        $article = Article::query()->create($this->columns($data) + [
            'slug' => $this->uniqueSlug((string) $data['title']),
            'created_by_user_id' => $by->id,
            'updated_by_user_id' => $by->id,
        ]);

        if ($image !== null) {
            $article->forceFill(['image_path' => $this->storeImage($image)])->save();
        }

        if ($authorImage !== null) {
            $article->forceFill(['author_image_path' => $this->storeImage($authorImage)])->save();
        }

        AuditLog::record(AuditEvent::ArticleCreated, $by, null, ['titel' => $article->title]);

        return $article;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Article $article, array $data, ?UploadedFile $image, bool $removeImage, User $by, ?UploadedFile $authorImage = null, bool $removeAuthorImage = false): Article
    {
        $article->fill($this->columns($data) + ['updated_by_user_id' => $by->id]);

        if ($image !== null) {
            $this->deleteImage($article);
            $article->image_path = $this->storeImage($image);
        } elseif ($removeImage) {
            $this->deleteImage($article);
            $article->image_path = null;
            $article->image_alt = null;
            $article->image_credit = null;
        }

        if ($authorImage !== null) {
            $this->deleteAuthorImage($article);
            $article->author_image_path = $this->storeImage($authorImage);
        } elseif ($removeAuthorImage) {
            $this->deleteAuthorImage($article);
            $article->author_image_path = null;
            $article->author_image_credit = null;
        }

        $changed = array_values(array_diff(array_keys($article->getDirty()), ['updated_at', 'updated_by_user_id']));
        $article->save();

        if ($changed !== []) {
            AuditLog::record(AuditEvent::ArticleUpdated, $by, null, ['titel' => $article->title, 'felder' => $changed]);
        }

        return $article;
    }

    public function delete(Article $article, User $by): void
    {
        AuditLog::record(AuditEvent::ArticleDeleted, $by, null, ['titel' => $article->title]);

        $this->deleteImage($article);
        $this->deleteAuthorImage($article);
        $article->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function columns(array $data): array
    {
        $clean = static fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null;
        $published = ($data['status'] ?? Article::STATUS_DRAFT) === Article::STATUS_PUBLISHED;

        return [
            'title' => trim((string) $data['title']),
            'subtitle' => $clean($data['subtitle'] ?? null),
            'teaser' => $clean($data['teaser'] ?? null),
            'body' => trim((string) $data['body']),
            'category' => $clean($data['category'] ?? null) ?? 'Themenartikel',
            'image_alt' => $clean($data['image_alt'] ?? null),
            'image_credit' => $clean($data['image_credit'] ?? null),
            'tags' => $this->tags($data['tags'] ?? null),
            'show_author' => (bool) ($data['show_author'] ?? false),
            'author_name' => $clean($data['author_name'] ?? null),
            'author_box' => (bool) ($data['author_box'] ?? false),
            'author_text' => filled(\App\Support\Html\RichText::clean($data['author_text'] ?? null)) ? \App\Support\Html\RichText::clean($data['author_text']) : null,
            'author_image_credit' => $clean($data['author_image_credit'] ?? null),
            'more_info' => \App\Support\Html\RichText::clean($data['more_info'] ?? null),
            'status' => $published ? Article::STATUS_PUBLISHED : Article::STATUS_DRAFT,
            // Das Datum entscheidet, ab wann der Artikel zu sehen ist (auch in der Zukunft); ohne Angabe gilt jetzt
            'published_at' => $published ? (filled($data['published_at'] ?? null) ? $data['published_at'] : now()) : null,
            'featured' => (bool) ($data['featured'] ?? false),
        ];
    }

    /**
     * Schlagwörter aus „a, b, c“: getrimmt, ohne Doppelte (Groß-/Kleinschreibung egal), höchstens zehn.
     *
     * @return list<string>|null
     */
    private function tags(mixed $value): ?array
    {
        $list = [];

        foreach (preg_split('/[,;\n]+/', (string) $value) ?: [] as $tag) {
            $tag = Str::limit(trim($tag), 40, '');

            if ($tag !== '' && ! in_array(mb_strtolower($tag), array_map('mb_strtolower', $list), true)) {
                $list[] = $tag;
            }
        }

        return $list === [] ? null : array_slice($list, 0, 10);
    }

    private function storeImage(UploadedFile $image): string
    {
        $extension = match ((string) $image->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $path = 'articles/'.Str::uuid().'.'.$extension;

        Storage::disk(self::DISK)->putFileAs(dirname($path), $image, basename($path));

        return $path;
    }

    private function deleteAuthorImage(Article $article): void
    {
        if (filled($article->author_image_path)) {
            Storage::disk(self::DISK)->delete($article->author_image_path);
        }
    }

    private function deleteImage(Article $article): void
    {
        if (filled($article->image_path)) {
            Storage::disk(self::DISK)->delete($article->image_path);
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title) ?: 'artikel', 100, '');
        $slug = $base;
        $n = 2;

        while (Article::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
