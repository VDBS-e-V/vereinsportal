@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Briefvorlagen']];
    $canManage = $user->can('letters.manage');
@endphp

<x-layouts.app title="Briefvorlagen" width="wide">
    <x-vdbs.layout.page-header title="Briefvorlagen" lead="Gestalten Sie Briefe in Word und schreiben Sie an die Stellen, die sich ändern, Platzhalter wie ${datum}. Das Portal füllt sie mit den Angaben zu Seminar, Schule und Person und gibt Ihnen fertige Word-Dateien." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('letters.variables')" icon="clipboard-list">Platzhalter</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('letters.sample')" icon="download">Beispielvorlage</x-vdbs.actions.button>
            @if ($canManage)
                <x-vdbs.actions.button :href="route('letters.create')" icon="plus">Neue Vorlage</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($templates->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Briefvorlagen" icon="file-text">
            <p>Laden Sie die Beispielvorlage herunter, passen Sie sie in Word an Ihren Briefbogen an und laden Sie sie hier hoch.</p>
            @if ($canManage)
                <x-slot:actions><x-vdbs.actions.button :href="route('letters.create')">Neue Vorlage</x-vdbs.actions.button></x-slot:actions>
            @endif
        </x-vdbs.feedback.empty-state>
    @else
        <form method="post" action="{{ route('letters.destroy-many') }}">
            @csrf
            <x-vdbs.data.table caption="Briefvorlagen" :columns="array_values(array_filter([$canManage ? ['key' => 'sel', 'label' => 'Auswahl'] : null, ['key' => 'name', 'label' => 'Name'], ['key' => 'audience', 'label' => 'Für'], ['key' => 'vars', 'label' => 'Platzhalter'], ['key' => 'file', 'label' => 'Datei']]))">
                @foreach ($templates as $template)
                    @php $unknown = $service->unknown($template->variableNames()); @endphp
                    <tr>
                        @if ($canManage)
                            <td><input type="checkbox" name="ids[]" value="{{ $template->id }}" aria-label="{{ $template->name }} auswählen"></td>
                        @endif
                        <td>
                            @if ($canManage)<a href="{{ route('letters.edit', $template) }}">{{ $template->name }}</a>@else {{ $template->name }} @endif
                            @if (filled($template->description))<br><small>{{ $template->description }}</small>@endif
                        </td>
                        <td>{{ $template->audienceLabel() }}</td>
                        <td>{{ count($template->variableNames()) }}@if ($unknown !== []) <x-vdbs.data.badge tone="warning">{{ count($unknown) }} unbekannt</x-vdbs.data.badge>@endif</td>
                        <td><a href="{{ route('letters.download', $template) }}">{{ $template->original_name }}</a></td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            @if ($canManage)
                <x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Die ausgewählten Vorlagen löschen?')">Ausgewählte löschen</x-vdbs.actions.button>
            @endif
        </form>
    @endif

    <section style="margin-top:var(--space-8)" aria-labelledby="how-title">
        <h2 id="how-title" style="font-size:var(--font-size-h3)">So funktioniert es</h2>
        <ol>
            <li>Laden Sie die <a href="{{ route('letters.sample') }}">Beispielvorlage</a> herunter und öffnen Sie sie in Word. Fügen Sie Ihren Briefbogen ein: Logo in die Kopfzeile, Anschrift und Kontakt in die Fußzeile.</li>
            <li>Schreiben Sie an die Stellen, die sich ändern, einen Platzhalter, zum Beispiel <code>${empfaenger_name}</code>. Alle Platzhalter stehen auf der Seite <a href="{{ route('letters.variables') }}">Platzhalter</a>.</li>
            <li>Speichern Sie die Datei als Word-Dokument (.docx) und laden Sie sie hier hoch.</li>
            <li>Öffnen Sie ein Seminar, wählen Sie unter „Schreiben“ die Vorlage und die Empfänger. Sie bekommen fertige Word-Dateien, die Sie noch ändern können.</li>
        </ol>
    </section>
</x-layouts.app>
