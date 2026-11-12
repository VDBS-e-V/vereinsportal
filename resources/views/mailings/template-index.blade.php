@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Mailvorlagen']];
@endphp

<x-layouts.app title="Mailvorlagen" width="wide">
    <x-vdbs.layout.page-header title="Mailvorlagen" lead="Vorlagen für Rundschreiben an Gruppen und der feste Rahmen für Mitteilungen an Teamende zu Seminaren. Alle Vorlagen arbeiten mit Platzhaltern." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('mailings.templates.snippets')" icon="clipboard-list">Textbausteine</x-vdbs.actions.button>
            <x-vdbs.actions.button :href="route('mailings.templates.create')" icon="plus">Neue Vorlage</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <section aria-labelledby="general-title">
        <h2 id="general-title" style="font-size:var(--font-size-h3)">Vorlagen für Rundschreiben</h2>

        <form method="get" action="{{ route('mailings.templates.index') }}" style="display:flex;gap:var(--space-3);align-items:flex-end;flex-wrap:wrap;margin-bottom:var(--space-4)">
            <x-vdbs.forms.input name="q" label="Suchen" :value="$search" />
            <x-vdbs.forms.select name="kategorie" label="Kategorie" placeholder="Alle Kategorien" :options="\App\Modules\Mailings\Support\MailCategories::TEMPLATES" :value="$category" />
            <x-vdbs.actions.button type="submit" variant="secondary" size="s">Filtern</x-vdbs.actions.button>
        </form>

        @if ($templates->isEmpty())
            <x-vdbs.feedback.empty-state title="Noch keine Vorlagen" icon="clipboard-list">
                <p>Legen Sie Vorlagen an, zum Beispiel für die Einladung zur Mitgliederversammlung. Beim Schreiben lassen sie sich übernehmen und anpassen.</p>
                <x-slot:actions><x-vdbs.actions.button :href="route('mailings.templates.create')">Neue Vorlage</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.feedback.empty-state>
        @else
            <form method="post" action="{{ route('mailings.templates.destroy-many') }}">
            @csrf
            <x-vdbs.data.table caption="Vorlagen für Rundschreiben" :columns="[['key' => 'sel', 'label' => 'Auswahl'], ['key' => 'name', 'label' => 'Name'], ['key' => 'category', 'label' => 'Kategorie'], ['key' => 'subject', 'label' => 'Betreff'], ['key' => 'changed', 'label' => 'Geändert']]">
                @foreach ($templates as $template)
                    <tr class="vdbs-table__row--link">
                        <td><input type="checkbox" name="ids[]" value="{{ $template->id }}" aria-label="{{ $template->name }} auswählen"></td>
                        <td><a class="vdbs-table__row-link" href="{{ route('mailings.templates.edit', $template) }}">{{ $template->name }}</a>@if (filled($template->description))<br><small>{{ $template->description }}</small>@endif</td>
                        <td>{{ \App\Modules\Mailings\Support\MailCategories::templateLabel($template->category) }}</td>
                        <td>{{ $template->subject }}</td>
                        <td>{{ $template->updated_at?->format('d.m.Y') }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Die ausgewählten Vorlagen löschen?')">Ausgewählte löschen</x-vdbs.actions.button>
            </form>
        @endif
    </section>

    <section aria-labelledby="team-title" style="margin-top:var(--space-8)">
        <h2 id="team-title" style="font-size:var(--font-size-h3)">Mitteilungen an Teamende zu Seminaren</h2>
        <p>Dieser Rahmen gilt für die Mitteilungen der Koordination. Sie ergänzt ihren eigenen Textabschnitt an der Stelle <code>{eigener_text}</code>.</p>
        <p>Betreff: <strong>{{ $team['subject'] }}</strong> <x-vdbs.data.badge :tone="$teamCustomized ? 'success' : 'neutral'">{{ $teamCustomized ? 'Angepasst' : 'Eingebaute Vorlage' }}</x-vdbs.data.badge></p>
        <x-vdbs.actions.button variant="secondary" :href="route('mailings.templates.team')" icon="pencil">Vorlage bearbeiten</x-vdbs.actions.button>
    </section>
</x-layouts.app>
