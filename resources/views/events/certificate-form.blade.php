@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Veranstaltungen', 'href' => route('events.public.index')], ['label' => 'Teilnahmebestätigung']];
@endphp

<x-layouts.app title="Teilnahmebestätigung" width="narrow">
    <x-vdbs.layout.page-header title="Teilnahmebestätigung" lead="Gib deinen Ticketcode und die E-Mail-Adresse deiner Anmeldung ein. Die Bestätigung gibt es, nachdem deine Anwesenheit eingetragen wurde." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('events.public.certificate.show')">
        <x-vdbs.forms.bot-guard />
        <x-vdbs.forms.input name="ticket" label="Ticketcode" :required="true" maxlength="20" autocomplete="off" hint="z. B. K7QM-4XWD" />
        <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" maxlength="254" autocomplete="email" />
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="file-text">Bestätigung anzeigen</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
