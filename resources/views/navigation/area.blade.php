@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'Navigation', 'href' => route('navigation.edit')], ['label' => $area->label()]];
    $selected = (array) old('roles', $row?->roles ?? []);
@endphp

<x-layouts.app title="Bereich bearbeiten" width="wide">
    <x-vdbs.layout.page-header :title="'Bereich bearbeiten „'.$area->label().'“'" lead="Anzeigename und Sichtbarkeit im Kopfmenü. Die Rollen schränken ein, wer den Bereich im Menü sieht. Ohne Auswahl gelten die Regeln des Portals." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('navigation.area.update', $area->value)" method="put">
        <x-vdbs.forms.form-section title="Allgemeines">
            <x-vdbs.forms.input name="label" label="Name im Kopfmenü" :optional="true" maxlength="60" :value="old('label', $row?->label)" :hint="'Leer lassen für „'.$area->label().'“.'" />
            <x-vdbs.forms.checkbox id="show-header" name="show_header" label="In der Kopfzeile anzeigen" :checked="old('show_header', $row?->show_header ?? true)" />
            <x-vdbs.forms.checkbox id="show-lists" name="show_lists" label="In Listen anzeigen (z. B. „Rollen & Bereiche“)" :checked="old('show_lists', $row?->show_lists ?? true)" />
        </x-vdbs.forms.form-section>
        <x-vdbs.forms.form-section title="Mehrere Optionen">
            <p class="vdbs-hint">Im Menü sichtbar nur für Personen mit mindestens einer dieser Rollen. Keine Auswahl: wie im Katalog festgelegt.</p>
            @foreach ($roles as $key => $label)
                <x-vdbs.forms.checkbox :id="'role-'.$key" name="roles[]" :value="$key" :label="$label" :checked="in_array($key, $selected, true)" />
            @endforeach
        </x-vdbs.forms.form-section>
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Speichern</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
