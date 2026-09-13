<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Start</p>
        <h1>Zugang zum Portal</h1>
        <p class="portal-page__lead">
            Hier finden Sie die passenden Wege für Anmeldung, Registrierung,
            Einladungen und die Wiederherstellung eines bestehenden Zugangs.
        </p>
    </header>

    <section class="portal-page__section">
        <div class="resource-list">
            <x-vdbs.resource-item
                title="Anmelden"
                :url="route('my.login')"
                description="Mit einem bestehenden und freigeschalteten Benutzerkonto anmelden."
                meta="Bestehendes Konto"
            />
            <x-vdbs.resource-item
                title="Registrieren"
                :url="route('my.registration.create')"
                description="Den vorhandenen öffentlichen Registrierungsweg starten."
                meta="Neuer Zugang"
            />
            <x-vdbs.resource-item
                title="Passwort vergessen"
                :url="route('my.password.request')"
                description="Ein neues Passwort für ein bestehendes Konto anfordern."
                meta="Kontohilfe"
            />
        </div>
    </section>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Einladung erhalten?</h2>
        </div>
        <div class="article">
            <p>
                Persönliche Einladungslinks führen direkt in den vorgesehenen
                Einrichtungsprozess. Verwenden Sie den vollständigen Link aus der
                Einladung, da er zeitlich begrenzt und nur einmal nutzbar ist.
            </p>
            <p>
                Falls ein Einladungslink nicht mehr funktioniert oder Sie nicht sicher
                sind, welcher Zugangsweg für Sie gilt, nutzen Sie bitte den Kontaktbereich.
            </p>
        </div>
        <div class="portal-page__actions">
            <a class="btn btn--secondary" href="{{ route('portal.contact') }}">Kontakt</a>
        </div>
    </section>
</div>
