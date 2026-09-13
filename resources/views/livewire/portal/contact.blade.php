<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

@php
    $contactEmail = (string) config('mail.from.address');
@endphp

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Start</p>
        <h1>Kontakt</h1>
        <p class="portal-page__lead">
            Unterstützung bei Fragen zum Portalzugang, Benutzerkonto oder zur Nutzung
            der freigeschalteten Bereiche.
        </p>
    </header>

    <section class="portal-page__section">
        <div class="contact-list">
            <x-vdbs.contact-block
                name="VDBS Vereinsportal"
                role="Allgemeine Portal-Anfragen"
                :email="$contactEmail"
            />
        </div>
    </section>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Vor der Kontaktaufnahme</h2>
        </div>
        <div class="resource-list">
            <x-vdbs.resource-item
                title="FAQ"
                :url="route('portal.faq')"
                description="Häufige Fragen zu Anmeldung, Passwort und Bereichen."
                meta="Selbsthilfe"
            />
            <x-vdbs.resource-item
                title="Zugang zum Portal"
                :url="route('portal.access')"
                description="Übersicht über Anmeldung, Registrierung und Einladungen."
                meta="Zugang"
            />
        </div>
    </section>
</div>
