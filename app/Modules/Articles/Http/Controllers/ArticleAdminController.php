<?php

namespace App\Modules\Articles\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Articles\Models\Article;
use App\Modules\Articles\Services\ArticleAdministration;
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

        $paginator = Article::query()
            ->when($search !== '', fn ($builder) => $builder->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($status !== null, fn ($builder) => $builder->where('status', $status))
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate(self::PER_PAGE)->withQueryString();

        return view('articles.admin-index', [
            'articles' => $paginator,
            'filters' => ['q' => $search !== '' ? $search : null, 'status' => $status],
            'active' => collect([
                $search !== '' ? ['label' => 'Suche: '.$search, 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
                $status !== null ? ['label' => 'Status: '.($status === Article::STATUS_PUBLISHED ? 'Veröffentlicht' : 'Entwurf'), 'href' => $request->fullUrlWithoutQuery(['status', 'page'])] : null,
            ])->filter()->values()->all(),
            'statusOptions' => [Article::STATUS_DRAFT => 'Entwurf', Article::STATUS_PUBLISHED => 'Veröffentlicht'],
        ]);
    }

    public function create(): View
    {
        return view('articles.admin-form', ['article' => null, 'values' => ['status' => Article::STATUS_DRAFT, 'category' => 'Themenartikel', 'published_at' => now()->format('Y-m-d\TH:i')]]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = $this->articles->create($this->validated($request), $request->file('image'), $this->actor($request));

        return redirect()->route('articles.admin.edit', $article)->with('success', 'Der Artikel wurde gespeichert.');
    }

    public function edit(Article $article): View
    {
        return view('articles.admin-form', ['article' => $article, 'values' => $article->only(['title', 'teaser', 'body', 'category', 'image_alt', 'image_credit', 'status', 'featured']) + ['published_at' => ($article->published_at ?? now())->format('Y-m-d\TH:i')]]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $this->articles->update($article, $this->validated($request, $article), $request->file('image'), $request->boolean('remove_image'), $this->actor($request));

        return redirect()->route('articles.admin.edit', $article)->with('success', 'Die Änderungen wurden gespeichert.');
    }

    public function destroy(Request $request, Article $article): RedirectResponse
    {
        $this->articles->delete($article, $this->actor($request));

        return redirect()->route('articles.admin.index')->with('success', 'Der Artikel wurde gelöscht.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Article $article = null): array
    {
        $hasImage = $request->hasFile('image') || ($article !== null && filled($article->image_path) && ! $request->boolean('remove_image'));

        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'teaser' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:20000'],
            'category' => ['nullable', 'string', 'max:60'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'image_alt' => [$hasImage ? 'required' : 'nullable', 'string', 'max:200'],
            'image_credit' => [$hasImage ? 'required' : 'nullable', 'string', 'max:200'],
            'remove_image' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in([Article::STATUS_DRAFT, Article::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'featured' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Bitte geben Sie einen Titel ein.',
            'body.required' => 'Bitte schreiben Sie den Artikeltext.',
            'body.max' => 'Der Text darf höchstens 20.000 Zeichen lang sein.',
            'teaser.max' => 'Der Anreißer darf höchstens 300 Zeichen lang sein.',
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
