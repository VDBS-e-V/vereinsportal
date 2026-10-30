<?php

namespace App\Modules\Articles\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Articles\Models\Article;
use App\Modules\Articles\Services\ArticleAdministration;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Öffentliche Artikelseiten (Bereich Start): Liste, Artikel und Bild. Entwürfe und zukünftig datierte Artikel sieht nur, wer Artikel verwalten darf. */
class PublicArticleController extends Controller
{
    private const PER_PAGE = 9;

    public function index(): View
    {
        return view('articles.index', ['articles' => Article::query()->visible()->orderByDesc('published_at')->orderByDesc('id')->paginate(self::PER_PAGE)]);
    }

    public function show(string $slug): View
    {
        $article = Article::query()->where('slug', $slug)->firstOrFail();

        abort_unless($article->isVisible() || Gate::allows('articles.manage'), 404);

        return view('articles.show', ['article' => $article, 'preview' => ! $article->isVisible()]);
    }

    public function image(string $slug): StreamedResponse
    {
        $article = Article::query()->where('slug', $slug)->firstOrFail();

        abort_unless(($article->isVisible() || Gate::allows('articles.manage')) && filled($article->image_path), 404);
        abort_unless(Storage::disk(ArticleAdministration::DISK)->exists($article->image_path), 404);

        return Storage::disk(ArticleAdministration::DISK)->response($article->image_path, null, ['Cache-Control' => 'public, max-age=3600']);
    }
}
