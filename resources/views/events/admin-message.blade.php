@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Veranstaltungen', 'href' => route('events.admin.index')], ['label' => $event->title, 'href' => route('events.admin.show', $event)], ['label' => 'Mitteilung']];
@endphp

<x-layouts.app title="Mitteilung senden" width="medium">
    <x-vdbs.layout.page-header title="Mitteilung an Angemeldete" lead="Eine E-Mail an alle bestätigten Angemeldeten dieser Veranstaltung, zum Beispiel bei einer Raumänderung." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('events.admin.messages.store', $event)">
        <x-vdbs.forms.radio-group name="audience" legend="Empfänger" :required="true" :options="collect($audiences)->map(fn ($label, $key) => $label.' ('.$counts[$key].' Adressen)')->all()" :value="old('audience', 'confirmed')" hint="Pro E-Mail-Adresse geht nur eine Nachricht raus, auch wenn eine Lehrkraft die ganze Klasse angemeldet hat." />
        <x-vdbs.forms.input name="subject" label="Betreff" :required="true" maxlength="150" />
        <x-vdbs.forms.textarea name="body" label="Text" :required="true" :rows="10" :rich="true" hint="Die Anrede „Hallo,“ und ein Link zur Veranstaltung werden automatisch ergänzt." />

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="send">Mitteilung senden</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('events.admin.show', $event)">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
