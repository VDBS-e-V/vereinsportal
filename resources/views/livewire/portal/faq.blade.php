<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Start</p>
        <h1>FAQ</h1>
        <p class="portal-page__lead">
            Antworten auf häufige Fragen rund um Anmeldung, Konto und Bereiche.
        </p>
    </header>

    <section class="portal-page__section">
        <div class="stack">
            <details>
                <summary><strong>Wo melde ich mich an?</strong></summary>
                <div class="article">
                    <p>Über die Anmeldeseite des Portals. Nach erfolgreicher Anmeldung führt der Weg zurück zur Startseite.</p>
                </div>
            </details>

            <details>
                <summary><strong>Ich habe mein Passwort vergessen. Was kann ich tun?</strong></summary>
                <div class="article">
                    <p>Nutzen Sie den Link „Passwort vergessen“. Der Wiederherstellungsprozess prüft das bestehende Konto und führt anschließend zur Passwortvergabe.</p>
                </div>
            </details>

            <details>
                <summary><strong>Warum sehe ich bestimmte Bereiche nicht?</strong></summary>
                <div class="article">
                    <p>Interne Bereiche werden nur angezeigt, wenn Ihr Konto die dafür benötigte Berechtigung besitzt. Nicht freigeschaltete Bereiche erscheinen nicht als gesperrte Platzhalter.</p>
                </div>
            </details>

            <details>
                <summary><strong>Wo ändere ich meine persönlichen Kontodaten?</strong></summary>
                <div class="article">
                    <p>Nach der Anmeldung finden Sie Profil, Kontodaten, Passwort und Zwei-Faktor-Authentifizierung im persönlichen Bereich.</p>
                </div>
            </details>

            <details>
                <summary><strong>Mein Einladungslink funktioniert nicht mehr.</strong></summary>
                <div class="article">
                    <p>Einladungen sind zeitlich begrenzt und nur einmal verwendbar. Wenden Sie sich an die zuständige Stelle, wenn ein neuer Link benötigt wird.</p>
                </div>
            </details>
        </div>
    </section>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Frage nicht beantwortet?</h2>
        </div>
        <div class="portal-page__actions">
            <a class="btn" href="{{ route('portal.contact') }}">Kontakt aufnehmen</a>
        </div>
    </section>
</div>
