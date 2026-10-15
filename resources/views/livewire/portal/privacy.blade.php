<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Diese Seite</p>
        <h1>Datenschutz</h1>
        <p class="portal-page__lead">
            Hinweise zum Umgang mit personenbezogenen Daten im Vereinsportal.
        </p>
    </header>

    <section class="portal-page__section">
        <div class="article">
            <h2>Portalbezogene Datenverarbeitung</h2>
            <p>
                Das Portal verarbeitet Daten, die für Anmeldung, Kontoverwaltung,
                Berechtigungen und die jeweils genutzten Vereinsfunktionen benötigt werden.
                Zugriffe auf interne Bereiche werden serverseitig anhand der vorhandenen
                Berechtigungen geprüft.
            </p>
            <p>
                Sicherheits- und Fachvorgänge können entsprechend der im System vorgesehenen
                Audit- und Nachvollziehbarkeitsfunktionen protokolliert werden.
            </p>
        </div>

        <x-vdbs.notice type="info">
            Der vollständige freigegebene Datenschutzhinweis wird vor Veröffentlichung
            mit der redaktionellen Vorlage abgeglichen. Diese Seite schafft bereits den
            vorgesehenen Portal-Einstieg und die Navigationsstruktur.
        </x-vdbs.notice>
    </section>

    <section class="portal-page__section">
        <div class="portal-page__actions">
            <a class="btn btn--secondary" href="{{ route('portal.contact') }}">
                Kontakt zu Datenschutzfragen
            </a>
        </div>
    </section>
</div>
