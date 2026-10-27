<x-layouts.app title="Anmelden">
    <div style="max-width:28rem;margin-inline:auto">
        <h1>Anmelden</h1>

        @if ($errors->any())
            <x-vdbs.feedback.alert variant="error" title="Anmeldung nicht möglich">
                Bitte prüfen Sie Ihre Eingaben.
            </x-vdbs.feedback.alert>
        @endif

        <form method="POST" action="{{ route('login.store') }}" novalidate>
            @csrf

            <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :value="old('email')" :error="$errors->first('email')" autocomplete="username" autofocus required />
            <x-vdbs.forms.input name="password" label="Passwort" type="password" :error="$errors->first('password')" autocomplete="current-password" required />

            <div class="vdbs-field">
                <label><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Angemeldet bleiben</label>
            </div>

            <x-vdbs.actions.button type="submit" :full="true">Anmelden</x-vdbs.actions.button>
        </form>
    </div>
</x-layouts.app>
