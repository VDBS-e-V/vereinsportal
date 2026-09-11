<x-layouts.public title="Portalzugang einrichten · VDBS Portal">
    <div class="portal-page portal-page--small">
        <header class="portal-page__header">
            <h1>Portalzugang einrichten</h1>
            <p class="portal-page__lead">
                Hallo {{ $person->first_name }}, legen Sie ein Passwort für Ihren bereits vorbereiteten Portalzugang fest.
            </p>
        </header>

        <x-vdbs.notice type="info" role="status">
            Der Zugang wird mit {{ $invitation->email }} und Ihrem vorhandenen Personendatensatz verknüpft.
        </x-vdbs.notice>

        <form class="portal-page__form form" action="{{ $formAction }}" method="post">
            @csrf

            <div class="form__field">
                <label class="form__label" for="password">Passwort</label>
                <input
                    class="form__control"
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    required
                    @error('password')
                        aria-invalid="true"
                        aria-describedby="portal-invitation-password-error"
                    @enderror
                >
                @error('password')
                    <p class="form__error" id="portal-invitation-password-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="form__field">
                <label class="form__label" for="password_confirmation">Passwort wiederholen</label>
                <input
                    class="form__control"
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                >
            </div>

            <p class="form__hint">Mindestens 10 Zeichen mit Groß- und Kleinbuchstaben, Zahl und Sonderzeichen.</p>

            <div class="portal-page__actions">
                <button class="btn" type="submit">Portalzugang einrichten</button>
            </div>
        </form>
    </div>
</x-layouts.public>
