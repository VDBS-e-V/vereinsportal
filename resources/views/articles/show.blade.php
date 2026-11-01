@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Artikel', 'href' => route('articles.public.index')], ['label' => $article->title]];
    $crumbs = \App\Support\PortalNavigation::breadcrumb($crumbs, request()->route()?->getName());
    $hasImage = filled($article->image_path) && filled($article->image_credit);
    $author = $article->authorName();
    $edited = $article->editedAfterPublishing();
    $tags = $article->tagList();
    $authorHtml = $article->authorTextHtml();
    $moreInfoHtml = $article->moreInfoHtml();
    $showAuthorBox = $article->author_box && ($authorHtml !== '' || filled($article->author_image_path));
    $authorImageUrl = filled($article->author_image_path) ? route('articles.public.author-image', $article->slug) : null;
    $categoryUrl = route('articles.public.index', ['kategorie' => $article->category]);
@endphp

<x-layouts.app :title="$article->title" width="full">
    @push('vdbs-breadcrumb')
        <x-vdbs.navigation.breadcrumb :items="$crumbs" />
    @endpush

    <article class="vdbs-article">
        <header class="vdbs-article__band" aria-labelledby="article-title">
            <x-vdbs.layout.container class="vdbs-article__head">
                @if ($preview)
                    <x-vdbs.feedback.alert variant="warning">Vorschau: Dieser Artikel ist noch nicht öffentlich sichtbar.</x-vdbs.feedback.alert>
                @endif

                <div class="vdbs-article__head-text">
                    @if (filled($article->category)) <p class="vdbs-article__category">{{ $article->category }}</p> @endif
                    <h1 class="vdbs-article__title" id="article-title">{{ $article->title }}</h1>
                    @if (filled($article->subtitle)) <p class="vdbs-article__subtitle">{{ $article->subtitle }}</p> @endif

                    <p class="vdbs-article__dates">
                        @if ($article->published_at)
                            <span>Veröffentlicht am <time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->format('d.m.Y') }}</time></span>
                        @endif
                        @if ($edited)
                            <span>Geändert am <time datetime="{{ $article->updated_at->toIso8601String() }}">{{ $article->updated_at->format('d.m.Y') }}</time></span>
                        @endif
                        @if ($article->show_author && $author)
                            <span>Von {{ $author }}</span>
                        @endif
                    </p>

                    @if (filled($article->teaser)) <p class="vdbs-article__lead">{{ $article->teaser }}</p> @endif
                </div>

                @if ($hasImage)
                    <x-vdbs.media.image :src="route('articles.public.image', $article->slug)" :alt="$article->image_alt ?? ''" :credit="$article->image_credit" credit-prefix="Quelle" class="vdbs-article__image" />
                @endif
            </x-vdbs.layout.container>
        </header>

        <x-vdbs.layout.container class="vdbs-article__main">
            <div class="vdbs-article__column">
                <x-vdbs.content.prose>{!! $article->html() !!}</x-vdbs.content.prose>

                @if ($showAuthorBox)
                    <aside class="vdbs-article__box vdbs-article__author" aria-labelledby="article-author-title">
                        @if ($authorImageUrl)
                            <x-vdbs.media.image :src="$authorImageUrl" :alt="$author ?? ''" :credit="$article->author_image_credit" credit-prefix="Bildquelle" class="vdbs-article__author-image" />
                        @endif
                        <div class="vdbs-article__author-text">
                            <h2 class="vdbs-article__box-title" id="article-author-title">Über die Autor:in</h2>
                            @if ($author) <p class="vdbs-article__author-name">{{ $author }}</p> @endif
                            @if ($authorHtml !== '') <x-vdbs.content.prose>{!! $authorHtml !!}</x-vdbs.content.prose> @endif
                        </div>
                    </aside>
                @endif

                @if ($moreInfoHtml !== '')
                    <aside class="vdbs-article__box vdbs-article__more" aria-labelledby="article-more-title">
                        <h2 class="vdbs-article__box-title" id="article-more-title">Weitere Informationen</h2>
                        <x-vdbs.content.prose>{!! $moreInfoHtml !!}</x-vdbs.content.prose>
                    </aside>
                @endif

                @if ($tags !== [])
                    <section class="vdbs-article__tags" aria-labelledby="article-tags-title">
                        <h2 class="vdbs-article__tags-title" id="article-tags-title">Schlagwörter</h2>
                        <x-vdbs.data.chip-list label="Schlagwörter">
                            @foreach ($tags as $tagName)
                                <x-vdbs.data.chip :label="$tagName" :href="route('articles.public.index', ['schlagwort' => $tagName])" />
                            @endforeach
                        </x-vdbs.data.chip-list>
                    </section>
                @endif

                <div class="vdbs-article__actions">
                    <x-vdbs.actions.button variant="primary" :href="route('articles.public.index')">Alle Artikel ansehen</x-vdbs.actions.button>
                    @if (filled($article->category))
                        <x-vdbs.actions.button variant="secondary" :href="$categoryUrl">Zur Kategorie „{{ $article->category }}“</x-vdbs.actions.button>
                    @endif
                </div>
            </div>
        </x-vdbs.layout.container>
    </article>
</x-layouts.app>
