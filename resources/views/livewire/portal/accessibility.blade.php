<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Diese Seite</p>
        <h1>Barrierefreiheit</h1>
        <p class="portal-page__lead">
            Informationen zur barrierearmen Nutzung des VDBS Vereinsportals.
        </p>
    </header>

    <section class="portal-page__section">
        <div class="article">
            <h2>Technische Grundlage</h2>
            <p>
                Das Portal verwendet eine gemeinsame Design- und Komponentenbibliothek
                mit Tastaturbedienung, sichtbaren Fokuszuständen, Skip-Link und
                semantischen Navigationsstrukturen als verbindlicher Basis.
            </p>
            <p>
                Responsive Darstellung, Screenreader-Nutzung, Kontrast, reduzierte
                Bewegung und aktuelle Browser werden im Rahmen der Design-QA geprüft.
            </p>
        </div>

        <x-vdbs.notice type="info">
            Die formale Barrierefreiheitserklärung und der endgültige redaktionelle Text
            werden vor Veröffentlichung mit der freigegebenen Vorlage abgeglichen.
        </x-vdbs.notice>
    </section>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Barriere melden</h2>
            <p>Wenn Sie bei der Nutzung auf ein Hindernis stoßen, geben Sie uns bitte Bescheid.</p>
        </div>
        <div class="portal-page__actions">
            <a class="btn" href="{{ route('portal.contact') }}">Kontakt aufnehmen</a>
        </div>
    </section>
</div>
