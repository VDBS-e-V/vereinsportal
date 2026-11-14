@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'FAQ']];
@endphp

<x-layouts.app title="FAQ verwalten" width="wide">
    <x-vdbs.layout.page-header title="FAQ" lead="Die Fragen und Antworten der FAQ-Seite des Portals, in Kategorien geordnet. Änderungen gelten sofort." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('portal.faq')" variant="secondary" icon="external-link">FAQ-Seite ansehen</x-vdbs.actions.button>
            <x-vdbs.actions.button :href="route('faq-admin.create')" icon="plus">Neue Kategorie</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($categories->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Kategorien" icon="mail">
            <p>Legen Sie eine Kategorie an und tragen Sie dort die Fragen und Antworten ein.</p>
            <x-slot:actions><x-vdbs.actions.button :href="route('faq-admin.create')">Neue Kategorie</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <form method="post" action="{{ route('faq-admin.bulk') }}">
            @csrf
            <x-vdbs.data.table caption="FAQ-Kategorien" :columns="[['key' => 'sel', 'label' => 'Auswahl'], ['key' => 'title', 'label' => 'Kategorie'], ['key' => 'count', 'label' => 'Fragen'], ['key' => 'page', 'label' => 'Auf der FAQ-Seite'], ['key' => 'status', 'label' => 'Status']]">
                @foreach ($categories as $category)
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="{{ $category->id }}" aria-label="{{ $category->title }} auswählen"></td>
                        <td>
                            <a href="{{ route('faq-admin.edit', $category) }}"><strong>{{ $category->title }}</strong></a>
                            @if (filled($category->description))<br><small>{{ $category->description }}</small>@endif
                        </td>
                        <td>{{ $category->entries_count }}</td>
                        <td>{{ $category->in_faq_page ? 'ja' : 'nein, nur über Kontaktthemen' }}</td>
                        <td>
                            @if ($category->is_active)
                                <x-vdbs.data.badge tone="success">sichtbar</x-vdbs.data.badge>
                            @else
                                <x-vdbs.data.badge tone="neutral">ausgeblendet</x-vdbs.data.badge>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>

            <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:center;margin-top:var(--space-4)">
                <label for="aktion">Mit den ausgewählten Kategorien:</label>
                <select name="aktion" id="aktion" class="vdbs-control vdbs-control--size-s" style="width:auto">
                    <option value="activate">sichtbar machen</option>
                    <option value="deactivate">ausblenden</option>
                    <option value="delete">löschen</option>
                </select>
                <x-vdbs.actions.button type="submit" variant="secondary" size="s" onclick="return confirm('Aktion für die ausgewählten Kategorien ausführen? Beim Löschen verschwinden auch ihre Fragen.')">Ausführen</x-vdbs.actions.button>
            </div>
        </form>
    @endif
</x-layouts.app>
