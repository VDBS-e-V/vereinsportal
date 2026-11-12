@php($crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Persönlicher Zugang']])

<x-layouts.app title="Persönlicher Zugang" width="narrow">
    <x-vdbs.layout.page-header title="Persönlicher Zugang" lead="Mit Ihrem Zugang sehen Sie offene Seminare und bewerben sich ohne eigenes Konto." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('access.verify')">
        <x-vdbs.forms.input name="email" type="email" label="E-Mail-Adresse" :required="true" autocomplete="email" />
        <x-vdbs.forms.input name="code" label="Code" :required="true" autocomplete="one-time-code" hint="Aus der E-Mail, z. B. K7QM4-XWD2K" maxlength="30" />
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="log-in">Anmelden</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    <h2 style="margin-top:var(--space-8);font-size:var(--font-size-h3)">Zugang verloren?</h2>
    <p>Geben Sie Ihre E-Mail-Adresse ein. Steht sie auf unserer Liste, schicken wir Ihnen einen neuen Zugang.</p>
    <x-vdbs.forms.form :action="route('access.resend')">
        <x-vdbs.forms.bot-guard />
        <x-vdbs.forms.input name="email" id="resend-email" type="email" label="E-Mail-Adresse" :required="true" autocomplete="email" />
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" variant="secondary" icon="mail">Neuen Zugang senden</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
