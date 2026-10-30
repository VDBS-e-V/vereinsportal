@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Artikel']];
@endphp

<x-layouts.app title="Artikel" width="wide">
    <x-vdbs.layout.page-header title="Artikel" lead="Aktuelle Themenartikel aus dem Verein." :breadcrumb="$crumbs" />

    @if ($articles->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Artikel" icon="clipboard-list">
            <p>Sobald Artikel veröffentlicht sind, erscheinen sie hier.</p>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.layout.grid :columns="3" align="start">
            @foreach ($articles as $article)
                <x-vdbs.cards.teaser
                    :title="$article->title"
                    :href="route('articles.public.show', $article->slug)"
                    :text="$article->excerpt()"
                    :image="filled($article->image_path) && filled($article->image_credit) ? route('articles.public.image', $article->slug) : null"
                    :imageAlt="$article->image_alt ?? ''"
                    :credit="$article->image_credit"
                    :category="$article->category" />
            @endforeach
        </x-vdbs.layout.grid>

        <div style="margin-top:var(--space-6)"><x-vdbs.navigation.pagination :paginator="$articles" /></div>
    @endif
</x-layouts.app>
