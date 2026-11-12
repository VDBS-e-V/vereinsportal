<?php

namespace App\Modules\Articles\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Articles\Models\Article;
use App\Modules\Articles\Services\ArticleAdministration;
use App\Support\Html\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Artikelverwaltung (Bereich Geschäftsstelle): Liste, Anlegen, Bearbeiten, Veröffentlichen, Löschen. Berechtigung articles.manage. */
class ArticleAdminController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private readonly ArticleAdministration $articles) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), [Article::STATUS_DRAFT, Article::STATUS_PUBLISHED], true) ? (string) $request->query('status') : null;
        $category = trim((string) $request->query('kategorie', '')) ?: null;
        $tags = Article::tagsFromQuery($request->query('schlagwort'));
        $featured = $request->query('empfohlen') === '1' ? '1' : null;

        $paginator = Article::query()->with('createdBy.person')->search($search)
            ->when($status !== null, fn ($builder) => $builder->where('status', $status))
            ->when($category !== null, fn ($builder) => $builder->where('category', $category))
            ->withAnyTag($tags)
            ->when($featured !== null, fn ($builder) => $builder->where('featured', true))
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate(self::PER_PAGE)->withQueryString();

        return view('articles.admin-index', [
            'articles' => $paginator,
            'filters' => ['q' => $search !== '' ? $search : null, 'status' => $status, 'category' => $category, 'tags' => $tags, 'featured' => $featured],
            'active' => collect([
                $search !== '' ? ['label' => 'Suche: '.$search, 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
                $status !== null ? ['label' => 'Status: '.($status === Article::STATUS_PUBLISHED ? 'Veröffentlicht' : 'Entwurf'), 'href' => $request->fullUrlWithoutQuery(['status', 'page'])] : null,
                $category !== null ? ['label' => 'Kategorie: '.$category, 'href' => $request->fullUrlWithoutQuery(['kategorie', 'page'])] : null,
                $featured !== null ? ['label' => 'Nur empfohlene', 'href' => $request->fullUrlWithoutQuery(['empfohlen', 'page'])] : null,
            ])->filter()->merge(collect($tags)->map(fn (string $t) => ['label' => 'Schlagwort: '.$t, 'href' => $request->fullUrlWithQuery(['schlagwort' => array_values(array_diff($tags, [$t])) ?: null, 'page' => null])]))->values()->all(),
            'statusOptions' => [Article::STATUS_DRAFT => 'Entwurf', Article::STATUS_PUBLISHED => 'Veröffentlicht'],
            'categoryOptions' => array_combine($c = Article::categoriesInUse(false), $c),
            'tagOptions' => array_combine($t = Article::tagsInUse(false), $t),
        ]);
    }

    public function create(): View
    {
        return view('articles.admin-form', ['article' => null, 'values' => ['status' => Article::STATUS_DRAFT, 'category' => 'Themenartikel', 'published_at' => now()->format('Y-m-d\TH:i')]]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = $this->articles->create($this->validated($request), $request->file('image'), $this->actor($request), $request->file('author_image'));

        return redirect($this->after($request, $article))->with('success', 'Der Artikel wurde gespeichert.');
    }

    public function edit(Article $article): View
    {
        return view('articles.admin-form', ['article' => $article, 'values' => $article->only(['title', 'subtitle', 'teaser', 'body', 'category', 'image_alt', 'image_credit', 'status', 'featured', 'show_author', 'author_name', 'author_box', 'author_text', 'author_image_credit', 'more_info']) + ['tags' => implode(', ', $article->tagList()), 'published_at' => ($article->published_at ?? now())->format('Y-m-d\TH:i')]]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $this->articles->update($article, $this->validated($request, $article), $request->file('image'), $request->boolean('remove_image'), $this->actor($request), $request->file('author_image'), $request->boolean('remove_author_image'));

        return redirect($this->after($request, $article))->with('success', 'Die Änderungen wurden gespeichert.');
    }

    public function destroy(Request $request, Article $article): RedirectResponse
    {
        $this->articles->delete($article, $this->actor($request));

        return redirect()->route('articles.admin.index')->with('success', 'Der Artikel wurde gelöscht.');
    }

    /** Ziel nach dem Speichern: Artikel (Standard), Liste oder neues Formular. */
    private function after(Request $request, Article $article): string
    {
        return match ($request->input('after')) {
            'list' => route('articles.admin.index'),
            'new' => route('articles.admin.create'),
            default => route('articles.public.show', $article->slug),
        };
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Article $article = null): array
    {
        $request->merge(['body' => RichText::normalize($request->input('body'))]);
        $hasAuthorImage = $request->hasFile('author_image') || ($article !== null && filled($article->author_image_path) && ! $request->boolean('remove_author_image'));

        $hasImage = $request->hasFile('image') || ($article !== null && filled($article->image_path) && ! $request->boolean('remove_image'));

        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'teaser' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:60000'],
            'category' => ['required', 'string', 'max:60'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'image_alt' => [$hasImage ? 'required' : 'nullable', 'string', 'max:200'],
            'image_credit' => [$hasImage ? 'required' : 'nullable', 'string', 'max:200'],
            'remove_image' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string', 'max:500'],
            'show_author' => ['nullable', 'boolean'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'author_box' => ['nullable', 'boolean'],
            'author_text' => ['nullable', 'string', 'max:5000'],
            'author_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'author_image_credit' => [$hasAuthorImage ? 'required' : 'nullable', 'string', 'max:200'],
            'remove_author_image' => ['nullable', 'boolean'],
            'more_info' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in([Article::STATUS_DRAFT, Article::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'featured' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Bitte geben Sie einen Titel ein.',
            'body.required' => 'Bitte schreiben Sie den Artikeltext.',
            'body.max' => 'Der Text ist zu lang.',
            'subtitle.max' => 'Der Untertitel darf höchstens 250 Zeichen lang sein.',
            'teaser.max' => 'Der Anreißer darf höchstens 300 Zeichen lang sein.',
            'author_image.mimes' => 'Das Autorenbild muss eine JPEG-, PNG- oder WebP-Datei sein.',
            'author_image.max' => 'Das Autorenbild darf höchstens 4 MB groß sein.',
            'author_image_credit.required' => 'Bitte nennen Sie die Quelle des Autorenbilds.',
            'image.mimes' => 'Bilder müssen JPEG-, PNG- oder WebP-Dateien sein.',
            'image.max' => 'Das Bild darf höchstens 4 MB groß sein.',
            'image_alt.required' => 'Bitte beschreiben Sie das Bild kurz (Alternativtext).',
            'image_credit.required' => 'Bitte nennen Sie die Bildquelle.',
            'status.in' => 'Bitte wählen Sie einen gültigen Status.',
            'published_at.date' => 'Bitte geben Sie ein gültiges Datum ein.',
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
