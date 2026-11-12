@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Teamkontakte']];
    $total = $contacts->total();
    $count = $total === 1 ? '1 Adresse' : $total.' Adressen';
    $active = collect([
        $filters['q'] !== null ? ['label' => 'Suche: '.$filters['q'], 'href' => request()->fullUrlWithoutQuery(['q', 'page'])] : null,
    ])->filter()->merge(collect($filters['zugang'])->map(fn (string $s) => ['label' => 'Zugang: '.$stateOptions[$s], 'href' => request()->fullUrlWithQuery(['zugang' => array_values(array_diff($filters['zugang'], [$s])) ?: null, 'page' => null])]))->values()->all();
@endphp

<x-layouts.app title="Teamkontakte" width="wide">
    <x-vdbs.layout.page-header title="Teamkontakte" lead="Die importierte Mailingliste. Jede Adresse bekommt einen persönlichen Zugang, mit dem sie sich auf Seminare bewerben kann." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('contacts.create')" icon="plus">Adresse hinzufügen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('contacts.import')" icon="upload">Liste importieren</x-vdbs.actions.button>
            @if ($stats['without'] > 0)
                <x-vdbs.actions.button icon="mail" data-vdbs-dialog-open="send-all">Zugänge an alle ohne Zugang senden</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="3" style="margin-bottom:var(--space-6)">
        <x-vdbs.data.stat label="Adressen" value="{{ $stats['total'] }}" icon="users" />
        <x-vdbs.data.stat label="Noch kein Zugang gesendet" value="{{ $stats['without'] }}" icon="mail" :tone="$stats['without'] > 0 ? 'warning' : 'neutral'" />
        <x-vdbs.data.stat label="Zugang benutzt" value="{{ $stats['used'] }}" icon="check" tone="success" />
    </x-vdbs.layout.grid>

    <x-vdbs.data.toolbar label="Kontakte filtern" :count="$count" :active="$active" :reset-href="route('contacts.index')" search-placeholder="E-Mail-Adresse oder Name suchen">
        <x-vdbs.forms.multi-select name="zugang" label="Zugang" placeholder="Alle" :options="$stateOptions" :value="$filters['zugang']" />
    </x-vdbs.data.toolbar>

    @if ($contacts->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Adressen gefunden" icon="users">
            <p>@if ($stats['total'] === 0) Es ist noch keine Liste importiert. @else Zu den gewählten Filtern gibt es keine Adressen. @endif</p>
            <x-slot:actions><x-vdbs.actions.button :href="route('contacts.import')" icon="upload">Liste importieren</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Teamkontakte" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'bulk-form'], ['key' => 'email', 'label' => 'E-Mail-Adresse'], ['key' => 'name', 'label' => 'Name'], ['key' => 'access', 'label' => 'Zugang'], ['key' => 'actions', 'label' => 'Aktionen']]">
            @foreach ($contacts as $row)
                @php
                    $credential = $row->credentials->first();
                    $blocked = isset($suppressed[$row->email]) || isset($unsubscribed[$row->email]);
                    $valid = $row->credentials->contains(fn ($c) => $c->isValid());
                    [$tone, $label] = match (true) {
                        $credential === null => ['neutral', 'Kein Zugang'],
                        ! $valid => ['error', 'Gesperrt'],
                        $row->credentials->contains(fn ($c) => $c->isValid() && $c->last_used_at !== null) => ['success', 'Benutzt'],
                        $credential->sent_at !== null => ['info', 'Gesendet'],
                        default => ['neutral', 'Nicht gesendet'],
                    };
                @endphp
                <tr>
                    <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $row->id }}" form="bulk-form" aria-label="{{ $row->email }} auswählen"></span></td>
                    <td>{{ $row->email }}@if ($blocked) <x-vdbs.data.badge tone="warning">gesperrt oder abgemeldet</x-vdbs.data.badge>@endif @if ($row->origin === 'link') <x-vdbs.data.badge tone="info">über Bewerbungslink</x-vdbs.data.badge>@endif</td>
                    <td>{{ $row->first_name || $row->last_name ? $row->displayName() : '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$tone">{{ $label }}</x-vdbs.data.badge>@if ($credential) <span class="vdbs-hint">Code endet auf …{{ $credential->code_hint }}</span>@endif</td>
                    <td>
                        <form method="post" action="{{ route('contacts.send', $row) }}" style="display:inline">@csrf
                            <x-vdbs.actions.button type="submit" variant="secondary" size="s" icon="mail" :disabled="$blocked">{{ $credential === null || $credential->sent_at === null ? 'Zugang senden' : 'Neu senden' }}</x-vdbs.actions.button>
                        </form>
                        @if ($valid)
                            <form method="post" action="{{ route('contacts.revoke', $row) }}" style="display:inline">@csrf
                                <x-vdbs.actions.button type="submit" variant="text" size="s" icon="lock">Sperren</x-vdbs.actions.button>
                            </form>
                        @endif
                        <x-vdbs.actions.button variant="text" size="s" icon="trash-2" data-vdbs-dialog-open="delete-{{ $row->id }}">Löschen</x-vdbs.actions.button>
                        <x-vdbs.overlays.confirm-dialog id="delete-{{ $row->id }}" title="Kontakt löschen?" variant="danger" :action="route('contacts.destroy', $row)" method="delete" confirm-label="Kontakt löschen">
                            Die Adresse {{ $row->email }} und ihr Zugang werden gelöscht. Beim nächsten Import kann sie wieder auftauchen.
                        </x-vdbs.overlays.confirm-dialog>
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <form id="bulk-form" method="post" style="margin-top:var(--space-4)">@csrf
            <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="bulk-form">Mit den Kästchen links wählen Sie mehrere Adressen aus, das Kästchen im Tabellenkopf wählt alle auf dieser Seite.</p>
            <x-vdbs.actions.button type="submit" variant="secondary" icon="mail" formaction="{{ route('contacts.send-selected') }}">Zugang an Auswahl senden</x-vdbs.actions.button>
            <x-vdbs.actions.button type="submit" variant="text" icon="lock" formaction="{{ route('contacts.revoke-selected') }}">Zugang der Auswahl sperren</x-vdbs.actions.button>
            <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" formaction="{{ route('contacts.delete-selected') }}">Auswahl löschen</x-vdbs.actions.button>
        </form>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$contacts" /></div>
    @endif

    <x-vdbs.overlays.confirm-dialog id="send-all" title="Zugänge senden?" :action="route('contacts.send-all')" confirm-label="Zugänge senden">
        {{ $stats['without'] }} Adressen ohne gesendeten Zugang bekommen eine E-Mail mit Link und Code. Gesperrte und abgemeldete Adressen werden ausgelassen. Der Versand läuft im Hintergrund.
    </x-vdbs.overlays.confirm-dialog>
</x-layouts.app>
