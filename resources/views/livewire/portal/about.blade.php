<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Start</p>
        <h1>Über das Portal</h1>
        <p class="portal-page__lead">
            Das VDBS Vereinsportal ist der zentrale digitale Einstieg für persönliche
            Kontofunktionen und die jeweils freigeschalteten Arbeitsbereiche des Vereins.
        </p>
    </header>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Ein Portal, klar getrennte Bereiche</h2>
        </div>
        <div class="article">
            <p>
                Persönliche Daten und Kontoeinstellungen bleiben im Bereich „Mein Profil“.
                Interne Arbeitsbereiche werden nur angezeigt, wenn der angemeldete Nutzer
                dafür eine passende Berechtigung besitzt.
            </p>
            <p>
                Der Bereich „Start“ bleibt der neutrale Einstieg. Von hier aus führen
                direkte Wege zu Informationen, Hilfe und den tatsächlich verfügbaren
                Portalbereichen.
            </p>
        </div>
    </section>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Wichtige Einstiege</h2>
        </div>
        <div class="resource-list">
            <x-vdbs.resource-item
                title="Zugang zum Portal"
                :url="route('portal.access')"
                description="Informationen zu Anmeldung, Einladung und Registrierung."
                meta="Zugang"
            />
            <x-vdbs.resource-item
                title="Häufige Fragen"
                :url="route('portal.faq')"
                description="Antworten zu Konto, Anmeldung und persönlichen Bereichen."
                meta="FAQ"
            />
            <x-vdbs.resource-item
                title="Kontakt"
                :url="route('portal.contact')"
                description="Unterstützung bei Fragen, die sich nicht direkt im Portal klären lassen."
                meta="Support"
            />
        </div>
    </section>
</div>
