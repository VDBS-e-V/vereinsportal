<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Konto</p>
        <h1>Kontoeinstellungen</h1>
        <p class="portal-page__lead">
            Alle Einstellungen, die das persönliche Benutzerkonto und dessen Sicherheit betreffen.
        </p>
    </header>

    <section class="portal-page__section">
        <div class="resource-list">
            <x-vdbs.resource-item
                title="Kontodaten"
                :url="route('my.profile')"
                description="Persönliche Daten, Kontaktangaben und Adresse verwalten."
                meta="Kontoeinstellungen"
            />
            <x-vdbs.resource-item
                title="2FA"
                :url="route('my.security')"
                description="Zwei-Faktor-Authentifizierung und Recovery Codes verwalten."
                meta="Sicherheit"
            />
            <x-vdbs.resource-item
                title="E-Mail-Änderung"
                :url="route('my.email-change')"
                description="Aktuelle E-Mail-Adresse einsehen oder eine Änderung anfordern."
                meta="Kontoeinstellungen"
            />
            <x-vdbs.resource-item
                title="Passwort ändern"
                :url="route('my.password.change')"
                description="Das aktuelle Kontopasswort sicher ändern."
                meta="Sicherheit"
            />
            <x-vdbs.resource-item
                title="Konto löschen"
                :url="route('my.account-deletion')"
                description="Die Löschung des Benutzerkontos anstoßen und den Status einsehen."
                meta="Konto"
            />
        </div>
    </section>
</div>
