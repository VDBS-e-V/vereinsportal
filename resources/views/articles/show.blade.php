@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Artikel', 'href' => route('articles.public.index')], ['label' => $article->title]];
    $crumbs = \App\Support\PortalNavigation::breadcrumb($crumbs, request()->route()?->getName());
    $hasImage = filled($article->image_path) && filled($article->image_credit);
    $author = $article->authorName();
@endphp

<x-layouts.app :title="$article->title" width="default">
    @push('vdbs-breadcrumb')
        <x-vdbs.navigation.breadcrumb :items="$crumbs" />
    @endpush

    @if ($preview)
        <x-vdbs.feedback.alert variant="warning">Vorschau: Dieser Artikel ist noch nicht öffentlich sichtbar.</x-vdbs.feedback.alert>
    @endif

    <article class="vdbs-article">
        <header class="vdbs-article__head" aria-labelledby="article-title">
            <div class="vdbs-article__head-text">
                @if (filled($article->category)) <p class="vdbs-article__category">{{ $article->category }}</p> @endif
                <h1 class="vdbs-article__title" id="article-title">{{ $article->title }}</h1>
                @if (filled($article->subtitle)) <p class="vdbs-article__subtitle">{{ $article->subtitle }}</p> @endif
                @if (filled($article->teaser)) <p class="vdbs-article__lead">{{ $article->teaser }}</p> @endif
            </div>

            @if ($hasImage)
                <x-vdbs.media.image :src="route('articles.public.image', $article->slug)" :alt="$article->image_alt ?? ''" :credit="$article->image_credit" credit-prefix="Quelle" class="vdbs-article__image" />
            @endif
        </header>

        <div class="vdbs-article__body">
            <x-vdbs.content.prose>{!! $article->html() !!}</x-vdbs.content.prose>

            <footer class="vdbs-article__foot">
                <dl class="vdbs-article__meta">
                    @if ($article->published_at)
                        <div><dt>Veröffentlicht</dt><dd><time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->format('d.m.Y') }}</time></dd></div>
                    @endif
                    @if ($article->editedAfterPublishing())
                        <div><dt>Geändert</dt><dd><time datetime="{{ $article->updated_at->toIso8601String() }}">{{ $article->updated_at->format('d.m.Y') }}</time></dd></div>
                    @endif
                    @if ($author)
                        <div><dt>Autor:in</dt><dd>{{ $author }}</dd></div>
                    @endif
                </dl>

                <div class="vdbs-article__actions">
                    <x-vdbs.actions.button variant="primary" :href="route('articles.public.index')">Alle Artikel ansehen</x-vdbs.actions.button>
                    @if (filled($article->category))
                        <x-vdbs.actions.button variant="secondary" :href="route('articles.public.index', ['kategorie' => $article->category])">Zur Kategorie „{{ $article->category }}“</x-vdbs.actions.button>
                    @endif
                </div>
            </footer>
        </div>
    </article>
</x-layouts.app>
