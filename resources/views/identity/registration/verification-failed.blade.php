<x-layouts.public>
    <x-slot:title>
        Registrierung nicht abgeschlossen
    </x-slot:title>

    <div class="portal-page portal-page--small">
        <header class="portal-page__header">
            <h1>
                Registrierung konnte nicht abgeschlossen werden
            </h1>
        </header>

        <x-vdbs.notice type="danger" role="alert">
            {{ $message }}
        </x-vdbs.notice>

        <p>
            Bitte prüfen Sie die Meldung und beginnen Sie
            die Registrierung gegebenenfalls erneut.
        </p>

        <div class="portal-page__actions">
            <a class="btn" href="{{ route('my.registration.create') }}">
                Registrierung neu starten
            </a>
        </div>
    </div>
</x-layouts.public>
