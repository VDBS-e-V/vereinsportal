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
    public function create(array $data, ?UploadedFile $image, User $by): Article
    {
        $article = Article::query()->create($this->columns($data) + [
            'slug' => $this->uniqueSlug((string) $data['title']),
            'created_by_user_id' => $by->id,
            'updated_by_user_id' => $by->id,
        ]);

        if ($image !== null) {
            $article->forceFill(['image_path' => $this->storeImage($image)])->save();
        }

        AuditLog::record(AuditEvent::ArticleCreated, $by, null, ['titel' => $article->title]);

        return $article;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Article $article, array $data, ?UploadedFile $image, bool $removeImage, User $by): Article
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
            'teaser' => $clean($data['teaser'] ?? null),
            'body' => trim((string) $data['body']),
            'category' => $clean($data['category'] ?? null) ?? 'Themenartikel',
            'image_alt' => $clean($data['image_alt'] ?? null),
            'image_credit' => $clean($data['image_credit'] ?? null),
            'status' => $published ? Article::STATUS_PUBLISHED : Article::STATUS_DRAFT,
            // Das Datum entscheidet, ab wann der Artikel zu sehen ist (auch in der Zukunft); ohne Angabe gilt jetzt
            'published_at' => $published ? (filled($data['published_at'] ?? null) ? $data['published_at'] : now()) : null,
            'featured' => (bool) ($data['featured'] ?? false),
        ];
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
