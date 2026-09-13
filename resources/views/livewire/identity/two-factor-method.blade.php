<?php

use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Support\PendingLogin;
use App\Modules\Identity\Support\TwoFactorRequirement;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public bool $emailAvailable = false;

    public bool $totpAvailable = false;

    public bool $recoveryAvailable = false;

    public ?string $preferredMethod = null;

    public function mount(
        PendingLogin $pendingLogin,
        TwoFactorRequirement $requirement,
    ): void {
        $user = $pendingLogin->user();

        if ($user === null) {
            $this->redirectRoute(
                'my.login',
                navigate: false,
            );

            return;
        }

        $this->emailAvailable = $requirement->canUseEmail($user);
        $this->totpAvailable = $requirement->canUseTotp($user);
        $this->recoveryAvailable = $requirement->hasRecoveryCodes($user);

        $preferred = $user->preferred_two_factor_method;

        $this->preferredMethod =
            $preferred instanceof TwoFactorMethodType
                && $requirement->canUse($user, $preferred)
                    ? $preferred->value
                    : null;
    }
};

?>

<div class="portal-page portal-page--small">
    <header class="portal-page__header">
        <h1>2FA-Methode auswählen</h1>

        <p class="portal-page__lead">
            Wählen Sie die Methode, mit der Sie diese Anmeldung bestätigen möchten.
            Nach einer erfolgreichen Anmeldung wird E-Mail oder Authenticator als Ihre bevorzugte Methode gespeichert.
        </p>
    </header>

    <section class="portal-page__section" aria-labelledby="two-factor-methods-heading">
        <div class="portal-page__section-header">
            <h2 id="two-factor-methods-heading">Verfügbare Methoden</h2>
        </div>

        <div class="resource-list">
            @if ($totpAvailable)
                <x-vdbs.resource-item
                    title="Authenticator-App"
                    :url="route('my.two-factor.challenge', ['method' => 'totp'])"
                    description="6-stelligen Code aus Ihrer Authenticator-App verwenden."
                    :meta="$preferredMethod === 'totp' ? 'Bevorzugt' : 'TOTP'"
                />
            @endif

            @if ($emailAvailable)
                <x-vdbs.resource-item
                    title="E-Mail-Code"
                    :url="route('my.two-factor.challenge', ['method' => 'email'])"
                    description="Sicherheitscode an Ihre bestätigte Konto-E-Mail senden."
                    :meta="$preferredMethod === 'email' ? 'Bevorzugt' : 'E-Mail'"
                />
            @endif

            @if ($recoveryAvailable)
                <x-vdbs.resource-item
                    title="Recovery Code"
                    :url="route('my.two-factor.challenge', ['method' => 'recovery'])"
                    description="Einen einmalig nutzbaren Recovery Code verwenden."
                    meta="Notfallzugang"
                />
            @endif
        </div>
    </section>

    <div class="portal-page__actions">
        <a class="btn btn--secondary" href="{{ route('my.two-factor.challenge') }}">
            Zur bevorzugten Methode
        </a>
    </div>
</div>
