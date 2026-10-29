@props(['i', 'row' => [], 'products' => [], 'defaults' => []])

{{-- Eine Position des Kostenvoranschlags: Produkt mit Stückzahl und den Angaben für den Rechner. $i ist der Index (oder __INDEX__ in der Vorlage). --}}
@php
    $value = fn (string $key) => $row[$key] ?? ($defaults[$key] ?? null);
@endphp

<fieldset data-vdbs-repeater-row data-vdbs-position style="border:0;padding:0;margin:0 0 var(--space-6);border-top:1px solid var(--border-default);padding-top:var(--space-4)">
    <legend class="vdbs-sr-only">Position</legend>

    <x-vdbs.forms.form-grid :columns="2">
        <x-vdbs.forms.select :id="'position-'.$i.'-product'" :name="'positions['.$i.'][product]'" label="Produkt" placeholder="Bitte wählen" :options="$products" :value="$row['product'] ?? null" data-vdbs-position-product />
        <x-vdbs.forms.input :id="'position-'.$i.'-days'" :name="'positions['.$i.'][days]'" label="Stückzahl" type="number" min="1" max="30" :value="$value('days')" hint="Tage, Treffen oder Sitzungen, je nach Produkt" />
        <x-vdbs.forms.input :id="'position-'.$i.'-team'" :name="'positions['.$i.'][team]'" label="Teamende" type="number" min="1" max="30" :value="$value('team')" />
        <x-vdbs.forms.input :id="'position-'.$i.'-hours'" :name="'positions['.$i.'][hours]'" label="Stunden pro Tag (auch Vor- und Nachbereitung)" type="number" min="0.5" max="14" step="0.5" :value="$value('hours')" />
        <x-vdbs.forms.input :id="'position-'.$i.'-leaders'" :name="'positions['.$i.'][leaders]'" label="Seminarleitung (Anzahl)" type="number" min="0" max="10" :value="$value('leaders')" />
        <x-vdbs.forms.input :id="'position-'.$i.'-group'" :name="'positions['.$i.'][group]'" label="Gruppengröße (Teilnehmende)" type="number" min="1" max="2000" :value="$value('group')" />
        <x-vdbs.forms.input :id="'position-'.$i.'-material_factor'" :name="'positions['.$i.'][material_factor]'" label="Materialpauschale (Menge)" type="number" min="0" max="5" step="0.1" :value="$value('material_factor')" hint="1 = volle Pauschale, 0,3 = Tagestreffen" />
        <x-vdbs.forms.input :id="'position-'.$i.'-material_trips'" :name="'positions['.$i.'][material_trips]'" label="Fahrten für Material" type="number" min="0" max="20" :value="$value('material_trips')" />
        <x-vdbs.forms.input :id="'position-'.$i.'-boards'" :name="'positions['.$i.'][boards]'" label="Pinnwände" type="number" min="0" max="20" :value="$value('boards')" />
    </x-vdbs.forms.form-grid>

    <x-vdbs.forms.checkbox :id="'position-'.$i.'-inside_abc'" :name="'positions['.$i.'][inside_abc]'" label="Innerhalb des Tarifbereichs ABC (Berlin)" :checked="(bool) $value('inside_abc')" />
    <x-vdbs.forms.checkbox :id="'position-'.$i.'-without_club_fee'" :name="'positions['.$i.'][without_club_fee]'" label="Ohne Vereinspauschale (je Position 65 €)" :checked="(bool) $value('without_club_fee')" />

    <x-vdbs.forms.input :id="'position-'.$i.'-title'" :name="'positions['.$i.'][title]'" label="Posten im Schreiben" :optional="true" maxlength="150" :value="$row['title'] ?? null" hint="Leer lassen für den Standardtext, z. B. „SV-Tage (3)“" />
    <x-vdbs.forms.textarea :id="'position-'.$i.'-content'" :name="'positions['.$i.'][content]'" label="Inhalt im Schreiben" :optional="true" maxlength="1000" :rows="3" :value="$row['content'] ?? null" hint="Eine Zeile je Aufzählungspunkt, leer lassen für den Standardtext" />

    <button type="button" class="vdbs-btn vdbs-btn--tertiary vdbs-btn--shape-line vdbs-btn--size-s vdbs-js-only" data-vdbs-repeater-remove>Position entfernen</button>
</fieldset>
