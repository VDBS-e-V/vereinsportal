<x-layouts.public>
    <x-slot:title>
        Registrierung abgeschlossen
    </x-slot:title>

    <div class="portal-page portal-page--small">
        <header class="portal-page__header">
            <h1>E-Mail bestätigt</h1>
        </header>

        <x-vdbs.notice type="success" role="status">
            <div class="stack stack--sm">
                <p>
                    Die Registrierung wurde erfolgreich abgeschlossen.
                </p>

                <p>
                    Ihr Benutzerkonto ist jetzt aktiv.
                </p>
            </div>
        </x-vdbs.notice>

        <div class="portal-page__actions">
            <a class="btn" href="{{ route('my.login') }}">
                Jetzt anmelden
            </a>
        </div>
    </div>
</x-layouts.public>
