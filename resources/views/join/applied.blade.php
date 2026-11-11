@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Mitglied werden']];
@endphp

<x-layouts.app title="Antrag eingegangen" width="narrow">
    <x-vdbs.layout.page-header title="Vielen Dank" :breadcrumb="$crumbs" />

    <x-vdbs.feedback.alert variant="success" title="Wir haben Ihnen eine E-Mail gesendet">
        Bitte öffnen Sie Ihr Postfach und bestätigen Sie Ihre E-Mail-Adresse. Danach können Sie sich im Portal anmelden. Über Ihren Antrag entscheidet der Vorstand; bis dahin steht Ihnen nur Ihr Konto zur Verfügung.
    </x-vdbs.feedback.alert>

    <section aria-labelledby="resend-heading" style="margin-top:var(--space-8)">
        <h2 id="resend-heading" style="font-size:var(--font-size-h3)">Keine E-Mail erhalten?</h2>
        <p>Prüfen Sie Ihren Spam-Ordner oder lassen Sie sich den Link erneut senden.</p>

        <x-vdbs.forms.form :action="route('join.resend')">
            <x-vdbs.forms.bot-guard />
            <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" :value="session('join_email')" autocomplete="email" />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="send">Link erneut senden</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </section>
</x-layouts.app>
