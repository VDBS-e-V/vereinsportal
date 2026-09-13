<?php

use App\Modules\Identity\Models\User;
use App\Support\PortalAreaCatalog;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

@php
    $user = auth()->user();
    $person = $user?->person;
    $displayName = trim(
        ($person?->first_name ?? '').' '.
        ($person?->last_name ?? '')
    );

    $visibleAreas = $user instanceof User
        ? app(PortalAreaCatalog::class)->switcherAreas(
            $user,
            includeDesign: false,
        )
        : [];
@endphp

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Start</p>
        <h1>
            @if ($displayName !== '')
                Willkommen, {{ $displayName }}
            @else
                Willkommen im VDBS Vereinsportal
            @endif
        </h1>

        <p class="portal-page__lead">
            Das Vereinsportal bündelt persönliche Einstellungen, Mitgliedschaft,
            interne Arbeitsbereiche und die wichtigsten Informationen zum Zugang.
        </p>

        <div class="portal-page__actions">
            @auth
                <a class="btn" href="{{ route('my.account.profile') }}">
                    <x-vdbs.icon name="user" size="18" />
                    <span>Mein Profil öffnen</span>
                </a>
                <a class="btn btn--secondary" href="{{ route('my.account') }}">
                    Konto verwalten
                </a>
            @else
                <a class="btn" href="{{ route('my.login') }}">
                    <x-vdbs.icon name="login" size="18" />
                    <span>Anmelden</span>
                </a>
                <a class="btn btn--secondary" href="{{ route('portal.access') }}">
                    Zugang zum Portal
                </a>
            @endauth
        </div>
    </header>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Das Portal auf einen Blick</h2>
            <p>Informationen, Einstieg und Unterstützung an einem Ort.</p>
        </div>

        <div class="resource-list">
            <x-vdbs.resource-item
                title="Über das Portal"
                :url="route('portal.about')"
                description="Wofür das Vereinsportal gedacht ist und welche Bereiche es bündelt."
                meta="Information"
            />
            <x-vdbs.resource-item
                title="Zugang zum Portal"
                :url="route('portal.access')"
                description="Anmeldung, Einladung, Registrierung und Wiederherstellung des Zugangs."
                meta="Zugang"
            />
            <x-vdbs.resource-item
                title="FAQ"
                :url="route('portal.faq')"
                description="Antworten auf häufige Fragen zur Nutzung des Portals."
                meta="Hilfe"
            />
            <x-vdbs.resource-item
                title="Kontakt"
                :url="route('portal.contact')"
                description="Kontaktweg für Fragen zum Portal und zum Benutzerkonto."
                meta="Support"
            />
        </div>
    </section>

    @auth
        <section class="portal-page__section">
            <div class="portal-page__section-header">
                <h2>Ihre Zugänge</h2>
                <p>Persönliche und freigeschaltete interne Bereiche.</p>
            </div>

            <div class="resource-list">
                <x-vdbs.resource-item
                    title="Mein Profil"
                    :url="route('my.account.profile')"
                    description="Persönliches Profil und zugehörige Kontobereiche öffnen."
                    meta="Persönlich"
                />

                @foreach ($visibleAreas as $area)
                    <x-vdbs.resource-item
                        :title="$area['label']"
                        :url="$area['url']"
                        description="Zum freigeschalteten Arbeitsbereich wechseln."
                        meta="Bereich"
                    />
                @endforeach
            </div>
        </section>
    @endauth
</div>
