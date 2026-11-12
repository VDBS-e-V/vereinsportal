@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => $area], ['label' => 'Rundschreiben', 'href' => route($base.'.index')], ['label' => 'Gesperrte Adressen']];
@endphp

<x-layouts.app title="Gesperrte Adressen" width="wide">
    <x-vdbs.layout.page-header title="Gesperrte Adressen" lead="An diese Adressen gehen keine Schreiben an Gruppen, auch keine Pflichtmitteilungen. Gesperrt wird manuell oder bei dauerhaften Zustellfehlern und Beschwerden." :breadcrumb="$crumbs" />

    <x-vdbs.forms.error-summary />

    <x-vdbs.layout.grid :columns="3">
        @foreach ($topics as $key => $label)
            <x-vdbs.data.stat :label="'Abgemeldet: '.$label" :value="$unsubscribeCounts[$key] ?? 0" icon="mail" />
        @endforeach
    </x-vdbs.layout.grid>

    <section aria-labelledby="add-title" style="margin-top:var(--space-6)">
        <h2 id="add-title" style="font-size:var(--font-size-h3)">Adresse sperren</h2>
        <x-vdbs.forms.form :action="route($base.'.suppressions.store')" method="post" :summary="false">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="email" type="email" label="E-Mail-Adresse" :required="true" maxlength="190" />
                <x-vdbs.forms.input name="note" label="Notiz" :optional="true" maxlength="250" hint="z. B. Bitte der Person, keine Post mehr" />
            </x-vdbs.forms.form-grid>
            <x-slot:actions>
                <x-vdbs.actions.button type="submit" icon="x">Sperren</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>
    </section>

    <section aria-labelledby="list-title" style="margin-top:var(--space-6)">
        <h2 id="list-title" style="font-size:var(--font-size-h3)">Gesperrt</h2>

        @if ($suppressions->isEmpty())
            <x-vdbs.feedback.empty-state title="Keine gesperrten Adressen" icon="mail"><p>Zurzeit ist keine Adresse gesperrt.</p></x-vdbs.feedback.empty-state>
        @else
            <x-vdbs.data.table caption="Gesperrte Adressen" :columns="[['key' => 'email', 'label' => 'Adresse'], ['key' => 'reason', 'label' => 'Grund'], ['key' => 'since', 'label' => 'Seit'], ['key' => 'note', 'label' => 'Notiz'], ['key' => 'action', 'label' => 'Aktion']]">
                @foreach ($suppressions as $suppression)
                    <tr>
                        <td>{{ $suppression->email }}</td>
                        <td>{{ $suppression->reasonLabel() }}</td>
                        <td>{{ $suppression->created_at?->format('d.m.Y') }}</td>
                        <td>{{ $suppression->note ?? '–' }}</td>
                        <td>
                            <form method="post" action="{{ route($base.'.suppressions.release', $suppression) }}">@csrf<x-vdbs.actions.button type="submit" variant="secondary" size="s">Freigeben</x-vdbs.actions.button></form>
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>

            <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$suppressions" /></div>
        @endif
    </section>
</x-layouts.app>
