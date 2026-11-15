@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'FAQ', 'href' => route('faq-admin.index')], ['label' => $category->title]];
    $formId = 'bulk-faq-entries';
@endphp

<x-layouts.app :title="'FAQ: '.$category->title" width="wide">
    <x-vdbs.layout.page-header :title="$category->title" lead="Die Fragen und Antworten dieser Kategorie. Öffnen Sie eine Frage, um sie zu bearbeiten." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('faq-admin.edit', $category)" variant="secondary" icon="settings">Kategorie-Einstellungen</x-vdbs.actions.button>
            <x-vdbs.actions.button :href="route('faq-admin.entries.create', $category)" icon="plus">Neue Frage</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($entries->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Fragen" icon="mail">
            <p>Legen Sie die erste Frage dieser Kategorie an.</p>
            <x-slot:actions><x-vdbs.actions.button :href="route('faq-admin.entries.create', $category)">Neue Frage</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <form method="post" action="{{ route('faq-admin.entries.bulk', $category) }}" id="{{ $formId }}">
            @csrf
            <x-vdbs.data.table caption="Fragen der Kategorie {{ $category->title }}" :columns="[['key' => 'sel', 'label' => 'Auswahl', 'selectAll' => $formId], ['key' => 'question', 'label' => 'Frage'], ['key' => 'answer', 'label' => 'Antwort'], ['key' => 'sort', 'label' => 'Reihenfolge'], ['key' => 'status', 'label' => 'Status']]">
                @foreach ($entries as $entry)
                    <tr>
                        <x-vdbs.data.table-check name="ids[]" :value="$entry->id" :label="$entry->question.' auswählen'" :form="$formId" />
                        <td><a href="{{ route('faq-admin.entries.edit', [$category, $entry]) }}"><strong>{{ $entry->question }}</strong></a></td>
                        <td>{{ \Illuminate\Support\Str::limit(strip_tags($entry->answer), 110) }}</td>
                        <td>{{ $entry->sort_order }}</td>
                        <td>
                            @if ($entry->is_active)
                                <x-vdbs.data.badge tone="success">sichtbar</x-vdbs.data.badge>
                            @else
                                <x-vdbs.data.badge tone="neutral">ausgeblendet</x-vdbs.data.badge>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>

            @error('ziel')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror

            <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:center;margin-top:var(--space-4)">
                <label for="aktion">Mit den ausgewählten Fragen:</label>
                <select name="aktion" id="aktion" class="vdbs-control vdbs-control--size-s" style="width:auto">
                    <option value="activate">sichtbar machen</option>
                    <option value="deactivate">ausblenden</option>
                    <option value="move">in eine andere Kategorie verschieben</option>
                    <option value="delete">löschen</option>
                </select>
                <label for="ziel">Zielkategorie (nur beim Verschieben):</label>
                <select name="ziel" id="ziel" class="vdbs-control vdbs-control--size-s" style="width:auto">
                    <option value="">-- bitte wählen --</option>
                    @foreach ($others as $id => $title)
                        <option value="{{ $id }}">{{ $title }}</option>
                    @endforeach
                </select>
                <x-vdbs.actions.button type="submit" variant="secondary" size="s" onclick="return confirm('Aktion für die ausgewählten Fragen ausführen?')">Ausführen</x-vdbs.actions.button>
                <span class="vdbs-hint" role="status" data-vdbs-select-count="{{ $formId }}"></span>
            </div>
        </form>
    @endif
</x-layouts.app>
