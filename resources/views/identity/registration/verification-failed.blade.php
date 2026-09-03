<x-layouts.public>
    <x-slot:title>
        Registrierung nicht abgeschlossen
    </x-slot:title>

    <div class="card">
        <h1>
            Registrierung konnte nicht abgeschlossen werden
        </h1>

        <p role="alert">
            {{ $message }}
        </p>

        <p>
            Bitte prüfen Sie die Meldung und beginnen Sie
            die Registrierung gegebenenfalls erneut.
        </p>
    </div>
</x-layouts.public>