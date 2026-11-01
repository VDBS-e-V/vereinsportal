@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Artikel']];
    $total = $articles->total();
    $count = $total === 1 ? '1 Artikel' : $total.' Artikel';
@endphp

<x-layouts.app title="Artikel" width="wide">
    <x-vdbs.layout.page-header title="Artikel" lead="Aktuelle Themenartikel aus dem Verein." :breadcrumb="$crumbs" />

    <x-vdbs.data.toolbar label="Artikel suchen und filtern" :count="$count" :active="$active" :reset-href="route('articles.public.index')" search-placeholder="Titel, Untertitel oder Schlagwort suchen">
        <x-vdbs.forms.select name="kategorie" label="Kategorie" placeholder="Alle" :options="$categoryOptions" :value="$filters['category']" />
        <x-vdbs.forms.multi-select name="schlagwort" label="Schlagwörter" :searchable="true" placeholder="Schlagwörter suchen" search-label="Schlagwörter filtern" :options="$tagOptions" :value="$filters['tags']" />
        <x-vdbs.forms.select name="jahr" label="Jahr" placeholder="Alle" :options="$yearOptions" :value="$filters['year']" />
        <x-vdbs.forms.select name="sortierung" label="Sortierung" :options="['neueste' => 'Neueste zuerst', 'aelteste' => 'Älteste zuerst']" :value="$filters['sort']" />
    </x-vdbs.data.toolbar>

    @if ($articles->isEmpty())
        <x-vdbs.feedback.empty-state :title="count($active) > 0 ? 'Keine passenden Artikel' : 'Noch keine Artikel'" icon="clipboard-list">
            <p>{{ count($active) > 0 ? 'Zu Ihrer Auswahl gibt es keine Artikel. Ändern Sie die Suche oder setzen Sie die Filter zurück.' : 'Sobald Artikel veröffentlicht sind, erscheinen sie hier.' }}</p>
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
