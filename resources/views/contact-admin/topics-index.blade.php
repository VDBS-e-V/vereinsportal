@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Kontaktformular']];
    $actionLabels = ['category' => 'Bereich', 'form' => 'Formular', 'default' => 'Standardformular', 'faq' => 'FAQ-Seite'];
    $rows = [];

    foreach ($roots as $root) {
        $rows[] = [$root, 0];

        foreach ($root->children as $child) {
            $rows[] = [$child, 1];
        }
    }

    $target = function ($topic) use ($standard): string {
        return match ($topic->action) {
            'form' => $topic->form?->name ?? 'Formular fehlt',
            'default' => $standard?->name ?? 'kein Standardformular',
            'faq' => $topic->faqCategory ? 'FAQ: '.$topic->faqCategory->title.' ('.$topic->faqCategory->entries->count().')' : 'FAQ-Kategorie fehlt',
            default => $topic->children->count().' Themen',
        };
    };
@endphp

<x-layouts.app title="Kontaktformular verwalten" width="wide">
    <x-vdbs.layout.page-header title="Kontaktformular" lead="Die Themenwahl im öffentlichen Kontaktbereich: Bereiche und Themen und die Formulare dahinter. Die FAQ pflegen Sie getrennt unter Geschäftsstelle › FAQ. Änderungen gelten sofort." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('contact-admin.topics.create', ['art' => 'category'])" variant="secondary" icon="plus">Neuer Bereich</x-vdbs.actions.button>
            <x-vdbs.actions.button :href="route('contact-admin.topics.create')" icon="plus">Neues Thema</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @include('contact-admin._nav', ['active' => 'topics'])

    @if ($rows === [])
        <x-vdbs.feedback.empty-state title="Noch keine Themen" icon="mail">
            <p>Ohne Themen führt der Kontaktbereich direkt zum Standardformular.</p>
            <x-slot:actions><x-vdbs.actions.button :href="route('contact-admin.topics.create')">Neues Thema</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <form method="post" action="{{ route('contact-admin.topics.bulk') }}" id="bulk-contact-topics">
            @csrf
            <x-vdbs.data.table caption="Themen des Kontaktbereichs" :columns="[['key' => 'sel', 'label' => 'Auswahl', 'selectAll' => 'bulk-contact-topics'], ['key' => 'title', 'label' => 'Bezeichnung'], ['key' => 'action', 'label' => 'Art'], ['key' => 'target', 'label' => 'Ziel'], ['key' => 'status', 'label' => 'Status']]">
                @foreach ($rows as [$topic, $level])
                    <tr>
                        <x-vdbs.data.table-check name="ids[]" :value="$topic->id" :label="$topic->title.' auswählen'" form="bulk-contact-topics" />
                        <td style="padding-left:{{ $level * 1.5 }}rem">
                            <a href="{{ route('contact-admin.topics.edit', $topic) }}"><strong>{{ $topic->title }}</strong></a>
                            @if (filled($topic->description))<br><small>{{ $topic->description }}</small>@endif
                        </td>
                        <td>{{ $actionLabels[$topic->action] ?? $topic->action }}</td>
                        <td>{{ $target($topic) }}</td>
                        <td>
                            @if (! $topic->is_active)
                                <x-vdbs.data.badge tone="neutral">ausgeblendet</x-vdbs.data.badge>
                            @elseif (! $topic->isCategory() && ! $topic->isReachable())
                                <x-vdbs.data.badge tone="warning">ohne Ziel</x-vdbs.data.badge>
                            @else
                                <x-vdbs.data.badge tone="success">sichtbar</x-vdbs.data.badge>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>

            <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:center;margin-top:var(--space-4)">
                <label for="aktion">Mit den ausgewählten Themen:</label>
                <select name="aktion" id="aktion" class="vdbs-control vdbs-control--size-s" style="width:auto">
                    <option value="activate">sichtbar machen</option>
                    <option value="deactivate">ausblenden</option>
                    <option value="delete">löschen</option>
                </select>
                <x-vdbs.actions.button type="submit" variant="secondary" size="s" onclick="return confirm('Aktion für die ausgewählten Themen ausführen?')">Ausführen</x-vdbs.actions.button>
                <span class="vdbs-hint" role="status" data-vdbs-select-count="bulk-contact-topics"></span>
            </div>
        </form>
    @endif
</x-layouts.app>
