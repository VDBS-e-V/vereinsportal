@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminare', 'href' => route('seminars.index')], ['label' => $seminar->title, 'href' => route('seminars.show', $seminar)], ['label' => 'Abschluss']];
@endphp

<x-layouts.app title="Seminar abschließen" width="medium">
    <x-vdbs.layout.page-header title="Seminar abschließen" lead="{{ $seminar->number }} {{ $seminar->title }}: Prüfen Sie die Checkliste. Beim Abschluss werden die Kennzahlen festgehalten." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('seminars.closure.store', $seminar)">
        <x-vdbs.forms.form-section title="Checkliste">
            @foreach ($checklist as $key => $item)
                <div style="margin-bottom:var(--space-4)">
                    <p style="margin:0"><strong>{{ $item['label'] }}</strong>
                        @if ($item['auto']) <x-vdbs.data.badge tone="success">erfüllt</x-vdbs.data.badge> @else <x-vdbs.data.badge tone="warning">offen</x-vdbs.data.badge> @endif
                    </p>
                    <p class="vdbs-hint" style="margin:0">{{ $item['hint'] }}</p>
                    @unless ($item['auto'])
                        <x-vdbs.forms.checkbox :name="'done['.$key.']'" :id="'done-'.$key" label="Erledigt, ich bestätige das." :checked="(bool) old('done.'.$key)" />
                        <x-vdbs.forms.input :name="'skip_reason['.$key.']'" :id="'skip-'.$key" label="Oder: Warum entfällt dieser Punkt?" :optional="true" maxlength="300" :value="old('skip_reason.'.$key)" />
                    @endunless
                </div>
            @endforeach
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Kennzahlen, die festgehalten werden">
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Termine">{{ $snapshot['sessions'] }} ({{ $snapshot['hours'] }} Stunden)</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Zeitraum">{{ $snapshot['first_day'] }} bis {{ $snapshot['last_day'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Erwartete Teilnehmende">{{ $snapshot['expected_participants'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Teamgröße">{{ $snapshot['team_size'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Kostenvoranschlag">{{ $snapshot['quote_total_cents'] !== null ? number_format($snapshot['quote_total_cents'] / 100, 2, ',', '.').' €' : '–' }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
            <p class="vdbs-hint">Nur Zahlen, keine Namen. Danach sind Verträge, Kostenvoranschläge und Rechnungen geschützt; wieder öffnen kann nur der Vorstand mit Begründung.</p>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="check">Seminar abschließen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('seminars.show', $seminar)">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
