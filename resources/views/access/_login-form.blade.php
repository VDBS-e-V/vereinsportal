@if (config('portal.login_image'))<h1>Anmelden</h1>@endif

<x-vdbs.forms.form :action="route('login.store')">
    <x-vdbs.forms.bot-guard />
    <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" autocomplete="username" autofocus />
    <x-vdbs.forms.password name="password" label="Passwort" :required="true" />
    <x-vdbs.forms.checkbox name="remember" label="Angemeldet bleiben" />
    @isset($service)
        @if ($service)
            <x-vdbs.forms.checkbox name="revoke_consent" label="Meine Zustimmung zur Weitergabe meiner Daten an {{ $service['name'] }} widerrufen." hint="Danach fragt das Portal beim Anmelden wieder nach Ihrer Zustimmung. Sie können sie dort ablehnen." />
        @endif
    @endisset

    <x-slot:actions>
        <x-vdbs.actions.button type="submit" :full="true" size="l">Anmelden</x-vdbs.actions.button>
    </x-slot:actions>
</x-vdbs.forms.form>

<p style="margin-top:var(--space-4)"><a href="{{ route('password.request') }}">Passwort vergessen?</a></p>
