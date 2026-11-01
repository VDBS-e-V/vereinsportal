<?php

namespace App\Modules\Articles\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Articles\Models\Article;
use App\Modules\Articles\Services\ArticleAdministration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Öffentliche Artikelseiten (Bereich Start): Liste, Artikel und Bild. Entwürfe und zukünftig datierte Artikel sieht nur, wer Artikel verwalten darf. */
class PublicArticleController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('kategorie', ''));
        $tags = Article::tagsFromQuery($request->query('schlagwort'));
        $year = (int) $request->query('jahr', 0);
        $oldest = $request->query('sortierung') === 'aelteste';

        $query = Article::query()->visible()->search($search)
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->withAnyTag($tags)
            ->when($year > 0, fn ($q) => $q->whereYear('published_at', $year))
            ->orderBy('published_at', $oldest ? 'asc' : 'desc')->orderBy('id', $oldest ? 'asc' : 'desc');

        $paginator = $query->paginate(self::PER_PAGE)->withQueryString();

        $active = collect([
            $search !== '' ? ['label' => 'Suche: '.$search, 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
            $category !== '' ? ['label' => 'Kategorie: '.$category, 'href' => $request->fullUrlWithoutQuery(['kategorie', 'page'])] : null,
            $year > 0 ? ['label' => 'Jahr: '.$year, 'href' => $request->fullUrlWithoutQuery(['jahr', 'page'])] : null,
        ])->filter()->merge(collect($tags)->map(fn (string $t) => ['label' => 'Schlagwort: '.$t, 'href' => $request->fullUrlWithQuery(['schlagwort' => array_values(array_diff($tags, [$t])) ?: null, 'page' => null])]))->values()->all();

        return view('articles.index', [
            'articles' => $paginator,
            'active' => $active,
            'filters' => ['category' => $category !== '' ? $category : null, 'tags' => $tags, 'year' => $year > 0 ? (string) $year : null, 'sort' => $oldest ? 'aelteste' : 'neueste'],
            'categoryOptions' => array_combine($c = Article::categoriesInUse(), $c),
            'tagOptions' => array_combine($t = Article::tagsInUse(), $t),
            'yearOptions' => array_combine($y = array_map('strval', Article::yearsInUse()), $y),
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::query()->where('slug', $slug)->firstOrFail();

        abort_unless($article->isVisible() || Gate::allows('articles.manage'), 404);

        return view('articles.show', ['article' => $article, 'preview' => ! $article->isVisible()]);
    }

    public function authorImage(string $slug): StreamedResponse
    {
        $article = Article::query()->where('slug', $slug)->firstOrFail();

        $path = $article->authorImagePath();

        abort_unless(($article->isVisible() || Gate::allows('articles.manage')) && filled($path), 404);
        abort_unless(Storage::disk(ArticleAdministration::DISK)->exists($path), 404);

        return Storage::disk(ArticleAdministration::DISK)->response($path, null, ['Cache-Control' => 'public, max-age=3600']);
    }

    public function image(string $slug): StreamedResponse
    {
        $article = Article::query()->where('slug', $slug)->firstOrFail();

        abort_unless(($article->isVisible() || Gate::allows('articles.manage')) && filled($article->image_path), 404);
        abort_unless(Storage::disk(ArticleAdministration::DISK)->exists($article->image_path), 404);

        return Storage::disk(ArticleAdministration::DISK)->response($article->image_path, null, ['Cache-Control' => 'public, max-age=3600']);
    }
}
