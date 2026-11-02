@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Mailvorlagen']];
@endphp

<x-layouts.app title="Mailvorlagen" width="wide">
    <x-vdbs.layout.page-header title="Mailvorlage für Teamende" lead="Der Rahmen für Mitteilungen der Koordination an Teamende zu Seminaren. Setzen Sie {eigener_text} an die Stelle, an der die Koordination ihren eigenen Abschnitt ergänzt." :breadcrumb="$crumbs">
        <x-slot:meta>
            <x-vdbs.data.badge :tone="$customized ? 'success' : 'neutral'">{{ $customized ? 'Angepasst' : 'Eingebaute Vorlage' }}</x-vdbs.data.badge>
        </x-slot:meta>
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.form :action="route('mailings.templates.update')" method="put">
        <x-vdbs.forms.form-section title="Vorlage">
            <x-vdbs.forms.input name="subject" label="Betreff" :required="true" maxlength="200" :value="$template['subject']" />
            <x-vdbs.forms.textarea name="body" label="Text" :required="true" maxlength="20000" :rows="14" :rich="true" :value="$body" hint="Ohne {eigener_text} hängt das Portal den Abschnitt der Koordination ans Ende." />
            <x-mailings.placeholders :placeholders="$placeholders" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            @if ($customized)
                <x-vdbs.actions.button variant="text" data-vdbs-dialog-open="reset-template">Eingebaute Vorlage wiederherstellen</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($customized)
        <x-vdbs.overlays.confirm-dialog id="reset-template" title="Eingebaute Vorlage wiederherstellen?" :action="route('mailings.templates.reset')" method="delete" confirm-label="Wiederherstellen">
            <p>Ihre angepasste Vorlage wird gelöscht. Danach gilt wieder der eingebaute Text.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
