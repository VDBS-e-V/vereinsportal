<?php

use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\AccountAccess;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public bool $hasMembershipArea = false;

    public bool $hasTeamArea = false;

    public function mount(AccountAccess $accountAccess): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->hasMembershipArea = $accountAccess->hasActiveRole(
            $user,
            RoleKey::Member,
        );
        $this->hasTeamArea = $accountAccess->hasActiveRole(
            $user,
            RoleKey::Team,
        );
    }
};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <p class="page-title__kicker">Persönlicher Bereich</p>
        <h1>Konto</h1>
        <p class="portal-page__lead">
            Verwalten Sie hier Profil, Kontoeinstellungen und die Bereiche, die zu Ihren aktiven Rollen gehören.
        </p>
    </header>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Bereiche</h2>
        </div>

        <div class="resource-list">
            <x-vdbs.resource-item
                title="Mein Profil"
                :url="route('my.account.profile')"
                description="Eigener Bereich für die Einstellungen des künftig öffentlich sichtbaren Profils."
                meta="Profil"
            />

            <x-vdbs.resource-item
                title="Kontoeinstellungen"
                :url="route('my.account.settings')"
                description="Kontodaten, Zwei-Faktor-Authentifizierung, E-Mail-Adresse, Passwort und Kontolöschung verwalten."
                meta="Konto"
            />

            @if ($hasMembershipArea)
                <x-vdbs.resource-item
                    title="Mitgliedschaft"
                    :url="route('my.membership')"
                    description="Status und Verlauf der eigenen Vereinsmitgliedschaft einsehen."
                    meta="Nur für Mitglieder"
                />
            @endif

            @if ($hasTeamArea)
                <article class="resource-item vdbs-resource-item">
                    <div class="resource-item__content vdbs-resource-item__content">
                        <h3 class="resource-item__title vdbs-resource-item__title">Teamendeneinstellungen</h3>
                        <p class="resource-item__description vdbs-resource-item__description">
                            Einstellungen für die Arbeit als Teamende werden mit den zugehörigen Fachfunktionen ergänzt.
                        </p>
                        <span class="resource-item__meta vdbs-resource-item__meta">Nur für Teamende · In Vorbereitung</span>
                    </div>
                </article>
            @endif

            <article class="resource-item vdbs-resource-item">
                <div class="resource-item__content vdbs-resource-item__content">
                    <h3 class="resource-item__title vdbs-resource-item__title">Meine Tickets</h3>
                    <p class="resource-item__description vdbs-resource-item__description">
                        Der persönliche Ticketbereich wird ergänzt, sobald das Ticketmodul verfügbar ist.
                    </p>
                    <span class="resource-item__meta vdbs-resource-item__meta">In Vorbereitung</span>
                </div>
            </article>
        </div>
    </section>
</div>
