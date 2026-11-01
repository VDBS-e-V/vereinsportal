@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Artikel']];
    $total = $articles->total();
    $count = $total === 1 ? '1 Artikel' : $total.' Artikel';
@endphp

<x-layouts.app title="Artikel" width="wide">
    <x-vdbs.layout.page-header title="Artikel" lead="Artikel für die Startseite und die öffentliche Artikelliste. Markierte Artikel erscheinen zuerst auf der Startseite." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('articles.public.index')" icon="eye">Öffentliche Liste</x-vdbs.actions.button>
            <x-vdbs.actions.button :href="route('articles.admin.create')" icon="plus">Artikel schreiben</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.data.toolbar label="Artikel filtern" :count="$count" :active="$active" :reset-href="route('articles.admin.index')" search-placeholder="Titel, Untertitel oder Schlagwort suchen">
        <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="$statusOptions" :value="$filters['status']" />
        <x-vdbs.forms.select name="kategorie" label="Kategorie" placeholder="Alle" :options="$categoryOptions" :value="$filters['category']" />
        <x-vdbs.forms.select name="schlagwort" label="Schlagwort" placeholder="Alle" :options="$tagOptions" :value="$filters['tag']" />
        <x-vdbs.forms.select name="empfohlen" label="Startseite" placeholder="Alle" :options="['1' => 'Nur empfohlene']" :value="$filters['featured']" />
    </x-vdbs.data.toolbar>

    @if ($articles->isEmpty())
        <x-vdbs.feedback.empty-state :title="count($active) > 0 ? 'Keine passenden Artikel' : 'Keine Artikel'" icon="clipboard-list">
            <p>{{ count($active) > 0 ? 'Zu Ihrer Auswahl gibt es keine Artikel. Ändern Sie die Suche oder setzen Sie die Filter zurück.' : 'Es sind noch keine Artikel geschrieben.' }}</p>
            <x-slot:actions><x-vdbs.actions.button :href="route('articles.admin.create')">Artikel schreiben</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Artikel" :columns="[['key' => 'title', 'label' => 'Titel'], ['key' => 'category', 'label' => 'Kategorie'], ['key' => 'tags', 'label' => 'Schlagwörter'], ['key' => 'author', 'label' => 'Autor:in'], ['key' => 'status', 'label' => 'Status'], ['key' => 'date', 'label' => 'Veröffentlichung'], ['key' => 'changed', 'label' => 'Geändert'], ['key' => 'featured', 'label' => 'Empfohlen']]">
            @foreach ($articles as $row)
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('articles.admin.edit', $row) }}">{{ $row->title }}</a>@if (filled($row->subtitle))<br><small>{{ $row->subtitle }}</small>@endif</td>
                    <td>{{ $row->category }}</td>
                    <td>{{ implode(', ', $row->tagList()) ?: '–' }}</td>
                    <td>{{ $row->authorName() ?? '–' }}</td>
                    <td>
                        @if ($row->status === \App\Modules\Articles\Models\Article::STATUS_PUBLISHED)
                            <x-vdbs.data.badge :tone="$row->isVisible() ? 'success' : 'warning'">{{ $row->isVisible() ? 'Veröffentlicht' : 'Geplant' }}</x-vdbs.data.badge>
                        @else
                            <x-vdbs.data.badge>Entwurf</x-vdbs.data.badge>
                        @endif
                    </td>
                    <td>{{ $row->published_at?->format('d.m.Y, H:i') ?? '–' }}</td>
                    <td>{{ $row->updated_at?->format('d.m.Y') ?? '–' }}</td>
                    <td>{{ $row->featured ? 'Ja' : '–' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$articles" /></div>
    @endif
</x-layouts.app>
