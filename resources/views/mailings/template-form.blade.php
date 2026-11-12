@php
    $editing = $template !== null;
    $pageTitle = $editing ? 'Vorlage bearbeiten' : 'Neue Vorlage';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Mailvorlagen', 'href' => route('mailings.templates.index')], ['label' => $editing ? 'Vorlage bearbeiten' : 'Neue Vorlage']];
@endphp

<x-layouts.app :title="$pageTitle" width="wide">
    <x-vdbs.layout.page-header :title="$editing ? 'Vorlage bearbeiten' : 'Neue Vorlage'" lead="Betreff und Text mit Platzhaltern. Beim Schreiben eines Rundschreibens lässt sich die Vorlage übernehmen und frei ändern." :breadcrumb="$crumbs">
        @if ($editing)
            <x-slot:actions>
                <x-vdbs.actions.button variant="danger" icon="trash-2" data-vdbs-dialog-open="delete-template">Löschen</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.form :action="$editing ? route('mailings.templates.update', $template) : route('mailings.templates.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Vorlage">
            <x-vdbs.forms.input name="name" label="Name der Vorlage" :required="true" maxlength="120" :value="$template?->name" hint="Unter diesem Namen erscheint sie beim Schreiben, z. B. Einladung zur Mitgliederversammlung" />
            <x-vdbs.forms.input name="description" label="Beschreibung" :optional="true" maxlength="250" :value="$template?->description" />
            <x-vdbs.forms.select name="category" label="Kategorie" :optional="true" placeholder="Ohne Kategorie" :options="$categories" :value="$template?->category" />
            <x-vdbs.forms.select name="audience" label="Zielgruppe" :optional="true" placeholder="Nicht festgelegt" :options="$audiences" :value="$template?->audience" hint="Nur ein Hinweis für die Auswahl, er schränkt den Versand nicht ein." />
            <x-vdbs.forms.input name="subject" label="Betreff" :required="true" maxlength="200" :value="$template?->subject" />
            <x-vdbs.forms.textarea name="body" label="Text" :required="true" maxlength="20000" :rows="14" :rich="true" :value="$body" />
            <x-mailings.placeholders :placeholders="$placeholders" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('mailings.templates.index')">Zurück zur Liste</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($editing && $versions->isNotEmpty())
        <section aria-labelledby="versions-title" style="margin-top:var(--space-8)">
            <h2 id="versions-title" style="font-size:var(--font-size-h3)">Frühere Stände</h2>
            <p>Beim Speichern bleibt der vorherige Stand erhalten (die letzten {{ \App\Modules\Mailings\Services\MailTemplates::KEEP_VERSIONS }}). Wiederherstellen macht den früheren Stand zur Vorlage; der jetzige Stand bleibt als Version erhalten.</p>
            <x-vdbs.data.table caption="Frühere Stände der Vorlage" :columns="[['key' => 'date', 'label' => 'Gespeichert'], ['key' => 'subject', 'label' => 'Betreff'], ['key' => 'by', 'label' => 'Von'], ['key' => 'action', 'label' => 'Aktion']]">
                @foreach ($versions as $version)
                    <tr>
                        <td>{{ $version->created_at?->format('d.m.Y, H:i') }}</td>
                        <td>{{ $version->subject }}</td>
                        <td>{{ $version->savedBy?->person?->fullName() ?? '–' }}</td>
                        <td><form method="post" action="{{ route('mailings.templates.restore', [$template, $version]) }}">@csrf<x-vdbs.actions.button type="submit" size="s" variant="secondary">Wiederherstellen</x-vdbs.actions.button></form></td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        </section>
    @endif

    @if ($editing)
        <x-vdbs.overlays.confirm-dialog id="delete-template" title="Vorlage löschen?" variant="danger" :action="route('mailings.templates.destroy', $template)" method="delete" confirm-label="Vorlage löschen">
            <p>Die Vorlage <strong>{{ $template->name }}</strong> wird gelöscht. Bereits gesendete Schreiben bleiben unverändert.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
