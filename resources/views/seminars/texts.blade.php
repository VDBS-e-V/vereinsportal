@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Texte']];
@endphp

<x-layouts.app title="Texte der Produkte" width="wide">
    <x-vdbs.layout.page-header title="Texte der Produkte" lead="Titel und Standardbeschreibung je Produkt im gedruckten Kostenvoranschlag. Sie gelten global; im Kostenrechner lässt sich nur etwas ergänzen." :breadcrumb="$crumbs" />

    <x-vdbs.forms.error-summary />

    <section aria-labelledby="placeholders-heading" style="margin-bottom:var(--space-8)">
        <h2 id="placeholders-heading" style="font-size:var(--font-size-h3)">Platzhalter</h2>
        <p>Platzhalter in geschweiften Klammern werden aus den Angaben der Position gefüllt, z. B. wird aus „SV-Tage ({stueckzahl})“ bei 3 Tagen „SV-Tage (3)“.</p>
        <x-vdbs.data.detail-list layout="inline">
            @foreach ($placeholders as $name => $meaning)
                <x-vdbs.data.detail-item :label="'{'.$name.'}'">{{ $meaning }}</x-vdbs.data.detail-item>
            @endforeach
        </x-vdbs.data.detail-list>
    </section>

    @if ($canManage)
        <x-vdbs.forms.form :action="route('seminar-texts.update')" method="put" :summary="false">
            @foreach ($products as $product)
                <x-vdbs.forms.form-section :title="$product->label()">
                    <x-vdbs.forms.input :name="'texts['.$product->value.'][title]'" :id="'text-'.$product->value.'-title'" label="Titel der Position" :required="true" maxlength="150" :value="$templates[$product->value]['title']" />
                    <x-vdbs.forms.textarea :name="'texts['.$product->value.'][content]'" :id="'text-'.$product->value.'-content'" label="Standardbeschreibung" :required="true" maxlength="1000" :rows="4" :value="$templates[$product->value]['content']" hint="Eine Zeile je Aufzählungspunkt" />
                    <p style="color:var(--text-muted);font-size:var(--font-size-small)">Beispiel mit den Voreinstellungen: <strong>{{ $examples[$product->value]['title'] }}</strong></p>
                </x-vdbs.forms.form-section>
            @endforeach

            <x-slot:actions><x-vdbs.actions.button type="submit" icon="save">Texte speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @else
        @foreach ($products as $product)
            <section aria-labelledby="text-{{ $product->value }}-heading" style="margin-bottom:var(--space-6)">
                <h2 id="text-{{ $product->value }}-heading" style="font-size:var(--font-size-h3)">{{ $product->label() }}</h2>
                <x-vdbs.data.detail-list layout="inline">
                    <x-vdbs.data.detail-item label="Titel der Position">{{ $templates[$product->value]['title'] }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Standardbeschreibung"><span style="white-space:pre-line">{{ $templates[$product->value]['content'] }}</span></x-vdbs.data.detail-item>
                </x-vdbs.data.detail-list>
            </section>
        @endforeach
    @endif
</x-layouts.app>
