<?php

use App\Modules\Identity\Actions\TwoFactor\BeginTotpSetupAction;
use App\Modules\Identity\Actions\TwoFactor\ConfirmTotpSetupAction;
use App\Modules\Identity\Actions\TwoFactor\DisableTwoFactorMethodAction;
use App\Modules\Identity\Actions\TwoFactor\EnableEmailTwoFactorAction;
use App\Modules\Identity\Actions\TwoFactor\RegenerateRecoveryCodesAction;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Exceptions\TwoFactorSetupFailed;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\TwoFactorRecoveryCode;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\TwoFactorRequirement;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public bool $twoFactorRequired = false;

    public bool $emailActive = false;

    public bool $emailAvailable = false;

    public bool $totpActive = false;

    public bool $hasRecoveryCodes = false;

    public ?int $totpMethodId = null;

    public ?string $totpSecret = null;

    public ?string $totpProvisioningUri = null;

    public string $totpCode = '';

    /**
     * Nur unmittelbar nach Erzeugung befüllt.
     *
     * @var list<string>
     */
    public array $recoveryCodes = [];

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(
        TwoFactorRequirement $requirement,
    ): void {
        $this->refreshState($requirement);
    }

    public function enableEmail(
        EnableEmailTwoFactorAction $enable,
        TwoFactorRequirement $requirement,
    ): void {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->resetMessages();

        try {
            $enable->execute(
                user: $user,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            );
        } catch (TwoFactorSetupFailed $exception) {
            $this->errorMessage =
                $exception->getMessage();

            return;
        }

        $this->statusMessage =
            'E-Mail-2FA wurde aktiviert.';

        $this->refreshState($requirement);
    }

    public function disableEmail(
        DisableTwoFactorMethodAction $disable,
        TwoFactorRequirement $requirement,
    ): void {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->resetMessages();

        /*
         * Bei einer Pflichtrolle bleibt der
         * E-Mail-Faktor als Fallback verfügbar.
         */
        if (
            $requirement
                ->isRequiredByRole($user)
        ) {
            $this->errorMessage =
                'Für Ihre aktuelle Rolle ist Zwei-Faktor-Authentifizierung verpflichtend. Der E-Mail-Faktor bleibt als Fallback verfügbar.';

            return;
        }

        $disable->execute(
            user: $user,
            type: TwoFactorMethodType::Email,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        );

        $this->statusMessage =
            'E-Mail-2FA wurde deaktiviert.';

        $this->recoveryCodes = [];

        $this->refreshState($requirement);
    }

    public function beginTotp(
        BeginTotpSetupAction $begin,
    ): void {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->resetMessages();

        try {
            $setup = $begin->execute(
                $user
            );
        } catch (TwoFactorSetupFailed $exception) {
            $this->errorMessage =
                $exception->getMessage();

            return;
        }

        $this->totpMethodId =
            $setup['method']->id;

        $this->totpSecret =
            $setup['secret'];

        $this->totpProvisioningUri =
            $setup['provisioning_uri'];

        $this->totpCode = '';

        $this->statusMessage =
            'Die neue Authenticator-Konfiguration wurde vorbereitet. Sie ist noch nicht aktiv.';
    }

    public function confirmTotp(
        ConfirmTotpSetupAction $confirm,
        TwoFactorRequirement $requirement,
    ): void {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->resetMessages();

        $this->validate([
            'totpCode' => [
                'required',
                'digits:6',
            ],
        ]);

        if ($this->totpMethodId === null) {
            $this->errorMessage =
                'Es ist keine offene TOTP-Einrichtung vorhanden.';

            return;
        }

        try {
            $codes = $confirm->execute(
                user: $user,
                methodId: $this->totpMethodId,
                code: $this->totpCode,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            );
        } catch (TwoFactorSetupFailed $exception) {
            $this->totpCode = '';

            $this->errorMessage =
                $exception->getMessage();

            return;
        }

        $this->recoveryCodes = $codes;

        $this->totpMethodId = null;
        $this->totpSecret = null;
        $this->totpProvisioningUri = null;
        $this->totpCode = '';

        $this->statusMessage =
            'TOTP wurde erfolgreich aktiviert. Bitte sichern Sie jetzt Ihre vier Recovery Codes.';

        $this->refreshState($requirement);
    }

    public function disableTotp(
        DisableTwoFactorMethodAction $disable,
        TwoFactorRequirement $requirement,
    ): void {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->resetMessages();

        $disable->execute(
            user: $user,
            type: TwoFactorMethodType::Totp,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        );

        $this->totpMethodId = null;
        $this->totpSecret = null;
        $this->totpProvisioningUri = null;
        $this->totpCode = '';
        $this->recoveryCodes = [];

        $this->statusMessage =
            'TOTP wurde deaktiviert.';

        $this->refreshState($requirement);
    }

    public function regenerateRecoveryCodes(
        RegenerateRecoveryCodesAction $regenerate,
        TwoFactorRequirement $requirement,
    ): void {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->resetMessages();

        try {
            $this->recoveryCodes =
                $regenerate->execute(
                    user: $user,
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
        } catch (TwoFactorSetupFailed $exception) {
            $this->errorMessage =
                $exception->getMessage();

            return;
        }

        $this->statusMessage =
            'Vier neue Recovery Codes wurden erzeugt. Alle vorherigen ungenutzten Codes sind jetzt ungültig.';

        $this->refreshState($requirement);
    }

    public function hideRecoveryCodes(): void
    {
        $this->recoveryCodes = [];

        $this->statusMessage =
            'Die Recovery Codes werden nicht erneut im Klartext angezeigt.';
    }

    private function refreshState(
        TwoFactorRequirement $requirement,
    ): void {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->twoFactorRequired =
            $requirement
                ->isRequiredByRole($user);

        $this->emailActive =
            TwoFactorMethod::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->where(
                    'type',
                    TwoFactorMethodType::Email,
                )
                ->whereNotNull('confirmed_at')
                ->whereNull('disabled_at')
                ->exists();

        $this->emailAvailable =
            $requirement
                ->canUseEmail($user);

        $this->totpActive =
            TwoFactorMethod::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->where(
                    'type',
                    TwoFactorMethodType::Totp,
                )
                ->whereNotNull('confirmed_at')
                ->whereNull('disabled_at')
                ->exists();

        $this->hasRecoveryCodes =
            TwoFactorRecoveryCode::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->whereNull('used_at')
                ->whereNull('invalidated_at')
                ->exists();
    }

    private function resetMessages(): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;

        $this->resetErrorBag();
    }
};

?>

<div class="portal-page">
    <header class="portal-page__header">
        <h1>Sicherheit und Zwei-Faktor-Authentifizierung</h1>

        @if ($twoFactorRequired)
            <x-vdbs.notice type="info" role="status">
                Für Ihre aktuelle Rolle ist
                Zwei-Faktor-Authentifizierung verpflichtend.
            </x-vdbs.notice>
        @else
            <p class="portal-page__lead">
                Zwei-Faktor-Authentifizierung ist für
                Ihr Konto freiwillig.
            </p>
        @endif
    </header>

    @if ($statusMessage !== null)
        <x-vdbs.notice type="success" role="status">
            {{ $statusMessage }}
        </x-vdbs.notice>
    @endif

    @if ($errorMessage !== null)
        <x-vdbs.notice type="danger" role="alert">
            {{ $errorMessage }}
        </x-vdbs.notice>
    @endif

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>E-Mail-Code</h2>
        </div>

        @if ($twoFactorRequired)
            <p class="icon-label">
                <x-vdbs.status type="info">Verfügbar</x-vdbs.status>
                <span>Pflicht-Fallback für Ihre aktuelle Rolle.</span>
            </p>
        @elseif ($emailActive)
            <p class="icon-label">
                <x-vdbs.status type="success">Aktiv</x-vdbs.status>
                <span>E-Mail-2FA ist aktiviert.</span>
            </p>

            <div class="portal-page__actions">
                <button
                    class="btn btn--secondary"
                    type="button"
                    wire:click="disableEmail"
                    wire:confirm="E-Mail-2FA wirklich deaktivieren?"
                >
                    E-Mail-2FA deaktivieren
                </button>
            </div>
        @else
            <p class="icon-label">
                <x-vdbs.status>Inaktiv</x-vdbs.status>
                <span>E-Mail-2FA ist derzeit nicht aktiviert.</span>
            </p>

            <div class="portal-page__actions">
                <button
                    class="btn"
                    type="button"
                    wire:click="enableEmail"
                >
                    E-Mail-2FA aktivieren
                </button>
            </div>
        @endif
    </section>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Authenticator-App (TOTP)</h2>
        </div>

        @if ($totpActive)
            <p class="icon-label">
                <x-vdbs.status type="success">Aktiv</x-vdbs.status>
                <span>TOTP ist aktiviert.</span>
            </p>

            <div class="portal-page__actions">
                <button
                    class="btn"
                    type="button"
                    wire:click="beginTotp"
                >
                    TOTP neu einrichten
                </button>

                <button
                    class="btn btn--secondary"
                    type="button"
                    wire:click="disableTotp"
                    wire:confirm="TOTP wirklich deaktivieren?"
                >
                    TOTP deaktivieren
                </button>
            </div>
        @else
            <p class="icon-label">
                <x-vdbs.status>Inaktiv</x-vdbs.status>
                <span>TOTP ist derzeit nicht aktiviert.</span>
            </p>

            <div class="portal-page__actions">
                <button
                    class="btn"
                    type="button"
                    wire:click="beginTotp"
                >
                    TOTP einrichten
                </button>
            </div>
        @endif

        @if ($totpMethodId !== null)
            <div class="portal-page__subsection">
                <h3>Authenticator einrichten</h3>

                <p>
                    Hinterlegen Sie dieses Secret
                    in Ihrer Authenticator-App:
                </p>

                <p class="portal-page__code">
                    <code>{{ $totpSecret }}</code>
                </p>

                <details class="disclosure">
                    <summary class="disclosure__summary">
                        Technische Einrichtungs-URI anzeigen
                    </summary>

                    <div class="disclosure__content">
                        <p class="portal-page__code">
                            <code>{{ $totpProvisioningUri }}</code>
                        </p>
                    </div>
                </details>

                <p>
                    Die neue Konfiguration wird erst
                    nach einem korrekten Code aktiviert.
                </p>

                <form class="portal-page__form form" wire:submit="confirmTotp">
                    <div class="form__field">
                        <label class="form__label" for="totpCode">
                            Aktueller 6-stelliger Code
                        </label>

                        <input
                            class="form__control"
                            id="totpCode"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            autocomplete="one-time-code"
                            wire:model="totpCode"
                            required
                            @error('totpCode')
                                aria-invalid="true"
                                aria-describedby="security-totp-code-error"
                            @enderror
                        >

                        @error('totpCode')
                            <p class="form__error" id="security-totp-code-error" role="alert">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="portal-page__actions">
                        <button class="btn" type="submit">
                            TOTP bestätigen und aktivieren
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </section>

    <section class="portal-page__section">
        <div class="portal-page__section-header">
            <h2>Recovery Codes</h2>
        </div>

        @if ($recoveryCodes !== [])
            <x-vdbs.notice type="warning" role="alert">
                Diese vier Codes werden nur jetzt
                im Klartext angezeigt. Bitte sicher
                außerhalb des Portals speichern.
            </x-vdbs.notice>

            <ol class="portal-page__code-list">
                @foreach ($recoveryCodes as $recoveryCode)
                    <li>
                        <code>{{ $recoveryCode }}</code>
                    </li>
                @endforeach
            </ol>

            <div class="portal-page__actions">
                <button
                    class="btn btn--secondary"
                    type="button"
                    wire:click="hideRecoveryCodes"
                >
                    Codes ausblenden
                </button>
            </div>
        @else
            @if ($hasRecoveryCodes)
                <p class="icon-label">
                    <x-vdbs.status type="success">Vorhanden</x-vdbs.status>
                    <span>
                        Recovery Codes sind hinterlegt und werden aus
                        Sicherheitsgründen nicht erneut angezeigt.
                    </span>
                </p>
            @else
                <p class="icon-label">
                    <x-vdbs.status>Keine</x-vdbs.status>
                    <span>Derzeit sind keine nutzbaren Recovery Codes hinterlegt.</span>
                </p>
            @endif

            @if (
                $twoFactorRequired
                || $emailActive
                || $totpActive
            )
                <div class="portal-page__actions">
                    <button
                        class="btn btn--secondary"
                        type="button"
                        wire:click="regenerateRecoveryCodes"
                        wire:confirm="Neue Recovery Codes erzeugen? Alle bisherigen ungenutzten Codes werden dadurch sofort ungültig."
                    >
                        Neue Recovery Codes erzeugen
                    </button>
                </div>
            @endif
        @endif
    </section>
</div>
