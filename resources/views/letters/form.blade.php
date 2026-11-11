@php
    $editing = $template !== null;
    $title = $editing ? 'Briefvorlage bearbeiten' : 'Neue Briefvorlage';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Briefvorlagen', 'href' => route('letters.index')], ['label' => $title]];
    $unknown = $editing ? $service->unknown($template->variableNames()) : [];
@endphp

<x-layouts.app :title="$title" width="wide">
    <x-vdbs.layout.page-header :title="$title" lead="Laden Sie eine Word-Datei (.docx) mit Platzhaltern hoch, zum Beispiel ${datum} oder ${empfaenger_name}. Sie bleibt privat im Portal." :breadcrumb="$crumbs" />

    @if ($editing)
        @if ($unknown !== [])
            <x-vdbs.feedback.alert variant="warning"><strong>Unbekannte Platzhalter:</strong> {{ implode(', ', array_map(fn ($name) => '${'.$name.'}', $unknown)) }}. Das Portal füllt sie nicht, sie bleiben im Brief stehen. Meist ist es ein Tippfehler oder Word hat den Platzhalter beim Tippen zerteilt. Prüfen Sie die <a href="{{ route('letters.variables') }}">Liste der Platzhalter</a>.</x-vdbs.feedback.alert>
        @endif
        <p>Gefundene Platzhalter: {{ $template->variableNames() === [] ? 'keine' : implode(', ', array_map(fn ($name) => '${'.$name.'}', $template->variableNames())) }}</p>
        <p>Aktuelle Datei: <a href="{{ route('letters.download', $template) }}">{{ $template->original_name }}</a> ({{ max(1, round($template->size / 1024)) }} KB)</p>
    @endif

    <x-vdbs.forms.form :action="$editing ? route('letters.update', $template) : route('letters.store')" :method="$editing ? 'put' : 'post'" :files="true">
        <x-vdbs.forms.form-section title="Vorlage">
            <x-vdbs.forms.input name="name" label="Name" :required="true" maxlength="120" :value="$template?->name" hint="z. B. Bestätigung an die Schule" />
            <x-vdbs.forms.input name="description" label="Beschreibung" :optional="true" maxlength="250" :value="$template?->description" />
            <x-vdbs.forms.select name="audience" label="Für" :options="$audiences" :value="$template?->audience ?? 'general'" hint="Nur eine Orientierung bei der Auswahl, sie schränkt nichts ein." />
            <x-vdbs.forms.file name="file" :label="$editing ? 'Neue Word-Datei (ersetzt die bisherige, freiwillig)' : 'Word-Datei (.docx)'" :required="! $editing" :optional="$editing" accept=".docx" hint="Höchstens 5 MB. Keine Dateien mit Makros." />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('letters.index')">Zurück zur Liste</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($editing)
        <form method="post" action="{{ route('letters.destroy', $template) }}" style="margin-top:var(--space-5)">@csrf @method('delete')
            <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Diese Vorlage löschen?')">Vorlage löschen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>
