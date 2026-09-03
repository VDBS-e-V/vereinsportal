<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public function mount(string $publicId): void
    {
        /*
         * Keine personenbezogenen Daten über diesen
         * öffentlichen Sicherheitshinweis ausgeben.
         *
         * Die signierte Route stellt sicher, dass der Link
         * tatsächlich vom System erzeugt wurde.
         */
    }
};

?>

<div class="card">
    <h1>Sicherheitshinweis</h1>

    <p>
        Die E-Mail-Adresse eines VDB-Kontos wurde geändert.
    </p>

    <p>
        Falls Sie diese Änderung nicht selbst veranlasst haben,
        wenden Sie sich bitte an die Vereinsadministration.
    </p>

    <p>
        Über diesen Link wird keine automatische Änderung
        oder Rücksetzung durchgeführt.
    </p>
</div>