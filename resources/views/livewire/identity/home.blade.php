<?php

use App\Modules\Identity\Actions\Auth\LogoutAction;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public function logout(
        LogoutAction $logout,
    ): void {
        $user = auth()->user();

        if ($user !== null) {
            $logout->execute(
                user: $user,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            );
        }

        $this->redirectRoute(
            'my.login',
            navigate: false,
        );
    }
};

?>

@php
    $user = auth()->user();
    $person = $user?->person;
    $displayName = trim(
        ($person?->first_name ?? '').' '.
        ($person?->last_name ?? '')
    );
@endphp

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Persönlicher Bereich</p>
        <h1>Mein Portal</h1>

        <p class="portal-page__lead">
            @if ($displayName !== '')
                Willkommen, {{ $displayName }}.
            @else
                Willkommen im VDBS Portal.
            @endif
            Hier finden Sie Ihre wichtigsten Konto- und Sicherheitseinstellungen.
        </p>
    </header>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Schnellzugriff</h2>
            <p>Häufig benötigte Einstellungen sind direkt erreichbar.</p>
        </div>

        <div class="resource-list">
            <x-vdbs.resource-item title="Profil" :url="route('my.profile')"
                description="Persönliche Daten, Kontaktangaben und Adresse verwalten." meta="Konto" />
            <x-vdbs.resource-item title="E-Mail-Adresse" :url="route('my.email-change')"
                description="Aktuelle E-Mail-Adresse einsehen oder eine Änderung anfordern." meta="Konto" />
            <x-vdbs.resource-item title="Sicherheit" :url="route('my.security')"
                description="Zwei-Faktor-Authentifizierung und Recovery Codes verwalten." meta="Sicherheit" />
            <x-vdbs.resource-item title="Passwort" :url="route('my.password.change')"
                description="Das aktuelle Kontopasswort ändern." meta="Sicherheit" />
        </div>
    </section>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Kontoinformationen</h2>
        </div>

        <dl class="metadata-list">
            <div>
                <dt>E-Mail-Adresse</dt>
                <dd>{{ $user?->email }}</dd>
            </div>
            <div>
                <dt>E-Mail bestätigt</dt>
                <dd>
                    @if ($user?->email_verified_at !== null)
                        <x-vdbs.status type="success">Bestätigt</x-vdbs.status>
                    @else
                        <x-vdbs.status type="warning">Ausstehend</x-vdbs.status>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Konto</dt>
                <dd><x-vdbs.status type="success">Aktiv</x-vdbs.status></dd>
            </div>
        </dl>
    </section>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Sitzung</h2>
            <p>Melden Sie sich auf gemeinsam genutzten Geräten nach der Nutzung ab.</p>
        </div>

        <div class="portal-page__actions">
            <button class="btn btn--secondary" type="button" wire:click="logout">
                <x-vdbs.icon name="logout" size="18" />
                <span>Abmelden</span>
            </button>
        </div>
    </section>
</div>
