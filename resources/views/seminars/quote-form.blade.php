@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Kostenvoranschläge', 'href' => route('seminar-quotes.index')], ['label' => 'Neu']];
    $templateDefaults = $defaults['sv_days'];
    $filled = count(array_filter($rows));
@endphp

<x-layouts.app title="Kostenvoranschlag berechnen" width="wide">
    <x-vdbs.layout.page-header title="Kostenvoranschlag berechnen" lead="Pro Position ein Produkt mit Stückzahl. „Berechnen“ zeigt die Vorschau, „Speichern“ legt den Kostenvoranschlag fest." :breadcrumb="$crumbs" />

    <x-vdbs.layout.grid :columns="2" align="start">
        <x-vdbs.forms.form :action="route('seminar-quotes.store')">
            <x-vdbs.forms.form-section title="Schule">
                <x-vdbs.forms.select name="partner_id" label="Schule" placeholder="Bitte wählen" :required="true" :options="$partnerOptions" :value="$values['partner_id'] ?? null" />
                <x-vdbs.forms.select name="seminar_id" label="Zugehöriges Seminar" placeholder="Keins" :optional="true" :options="$seminarOptions" :value="$values['seminar_id'] ?? null" hint="Verknüpft den Kostenvoranschlag mit dem Seminar (nötig für den Status „Bestätigt“)" />
            </x-vdbs.forms.form-section>

            <x-vdbs.forms.form-section title="Positionen" description="Jede Position ist ein aufklappbarer Bereich. Die Voreinstellungen richten sich nach dem Produkt und lassen sich ändern. Titel und Standardbeschreibung kommen aus den Texten (Seite „Texte“), hier lässt sich nur etwas ergänzen. Jede Position wird einzeln berechnet, auch die Vereinspauschale.">
                <div data-vdbs-repeater data-vdbs-repeater-next="{{ $nextIndex }}" data-vdbs-position-defaults="{{ json_encode($defaults) }}" data-vdbs-position-units="{{ json_encode($units) }}">
                    <div class="vdbs-accordion" data-vdbs-repeater-items>
                        @foreach ($rows as $i => $row)
                            @php
                                $hasError = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'positions.'.$i.'.'));
                                $open = $hasError || ($row !== [] && $filled === 1);
                            @endphp
                            <x-seminars.position-row :i="$i" :row="$row" :products="$products" :units="$units" :open="$open" :defaults="$row === [] ? $templateDefaults : []" />
                        @endforeach
                    </div>

                    <template data-vdbs-repeater-template>
                        <x-seminars.position-row i="__INDEX__" :products="$products" :units="$units" :open="true" :defaults="$templateDefaults" />
                    </template>

                    <button type="button" class="vdbs-btn vdbs-btn--secondary vdbs-btn--shape-outline vdbs-btn--size-m vdbs-js-only" data-vdbs-repeater-add>
                        <x-vdbs.icon name="plus" :size="18" /> Weitere Position hinzufügen
                    </button>
                </div>
            </x-vdbs.forms.form-section>

            <x-slot:actions>
                <x-vdbs.actions.button type="submit" name="action" value="preview" variant="secondary" icon="refresh-cw">Berechnen</x-vdbs.actions.button>
                <x-vdbs.actions.button type="submit" name="action" value="save" icon="save">Kostenvoranschlag speichern</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="tertiary" :href="route('seminar-quotes.index')">Abbrechen</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>

        <section aria-labelledby="preview-heading">
            <h2 id="preview-heading" style="font-size:var(--font-size-h3)">Vorschau</h2>
            @if ($preview === null)
                <p>Wählen Sie „Berechnen“, um die Positionen zu sehen.</p>
            @else
                @foreach ($preview['positions'] as $n => $position)
                    <h3 style="font-size:var(--font-size-h4)">Position {{ $n + 1 }}: {{ $position['title'] }}</h3>
                    <ul>
                        @foreach (array_merge(\App\Modules\Seminars\Support\QuoteTexts::lines($position['content']), \App\Modules\Seminars\Support\QuoteTexts::lines($position['extra'])) as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                    @include('seminars._lines', ['lines' => $position['lines'], 'total' => $position['total_cents'], 'days' => $position['inputs']['days'], 'totalLabel' => 'Summe der Position'])
                @endforeach
                <p><strong>Endsumme: {{ \App\Modules\Seminars\Support\Money::format($preview['total_cents']) }}</strong></p>
                <p style="color:var(--text-muted);font-size:var(--font-size-small)">Preisstand: {{ $preview['price_list']->label }} (gültig ab {{ $preview['price_list']->valid_from->format('d.m.Y') }}). Noch nicht gespeichert.</p>
            @endif
        </section>
    </x-vdbs.layout.grid>
</x-layouts.app>
