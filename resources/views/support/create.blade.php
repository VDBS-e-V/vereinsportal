@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Konto', 'href' => route('account.home')], ['label' => 'Meine Tickets', 'href' => route('support.mine')], ['label' => 'Neues Ticket']];
@endphp

<x-layouts.app title="Neues Ticket" width="medium">
    <x-vdbs.layout.page-header title="Neues Ticket" lead="Beschreiben Sie Ihr Anliegen. Das Portal-Team antwortet im Ticket; Sie finden es danach unter „Meine Tickets“. Schauen Sie vorher gern in die FAQ." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('support.store')">
        <x-vdbs.forms.input name="subject" label="Betreff" :required="true" maxlength="200" />
        <x-vdbs.forms.select name="category" label="Worum geht es?" :required="true" :options="$categories" :value="old('category', 'question')" />
        <x-vdbs.forms.textarea name="message" label="Ihr Anliegen" :required="true" maxlength="10000" :counter="true" :rows="8" hint="Was möchten Sie erreichen, was ist passiert? Bitte keine Passwörter angeben." />
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="send">Ticket anlegen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('support.mine')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
