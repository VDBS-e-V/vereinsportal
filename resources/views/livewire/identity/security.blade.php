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

<div class="card">
    <h1>Sicherheit und Zwei-Faktor-Authentifizierung</h1>

    @if ($twoFactorRequired)
        <p role="status">
            Für Ihre aktuelle Rolle ist
            Zwei-Faktor-Authentifizierung verpflichtend.
        </p>
    @else
        <p>
            Zwei-Faktor-Authentifizierung ist für
            Ihr Konto freiwillig.
        </p>
    @endif

    @if ($statusMessage !== null)
        <p role="status">
            {{ $statusMessage }}
        </p>
    @endif

    @if ($errorMessage !== null)
        <p role="alert">
            {{ $errorMessage }}
        </p>
    @endif

    <section>
        <h2>E-Mail-Code</h2>

        @if ($twoFactorRequired)
            <p>
                Der E-Mail-Code steht für Ihre
                Pflichtrolle als zweiter Faktor zur Verfügung.
            </p>
        @elseif ($emailActive)
            <p>
                E-Mail-2FA ist aktiviert.
            </p>

            <button
                type="button"
                wire:click="disableEmail"
                wire:confirm="E-Mail-2FA wirklich deaktivieren?"
            >
                E-Mail-2FA deaktivieren
            </button>
        @else
            <p>
                E-Mail-2FA ist derzeit nicht aktiviert.
            </p>

            <button
                type="button"
                wire:click="enableEmail"
            >
                E-Mail-2FA aktivieren
            </button>
        @endif
    </section>

    <section>
        <h2>Authenticator-App (TOTP)</h2>

        @if ($totpActive)
            <p>
                TOTP ist aktiviert.
            </p>

            <button
                type="button"
                wire:click="beginTotp"
            >
                TOTP neu einrichten
            </button>

            <button
                type="button"
                wire:click="disableTotp"
                wire:confirm="TOTP wirklich deaktivieren?"
            >
                TOTP deaktivieren
            </button>
        @else
            <p>
                TOTP ist derzeit nicht aktiviert.
            </p>

            <button
                type="button"
                wire:click="beginTotp"
            >
                TOTP einrichten
            </button>
        @endif

        @if ($totpMethodId !== null)
            <div>
                <h3>Authenticator einrichten</h3>

                <p>
                    Hinterlegen Sie dieses Secret
                    in Ihrer Authenticator-App:
                </p>

                <p>
                    <code>{{ $totpSecret }}</code>
                </p>

                <details>
                    <summary>
                        Technische Einrichtungs-URI anzeigen
                    </summary>

                    <p>
                        <code>
                            {{ $totpProvisioningUri }}
                        </code>
                    </p>
                </details>

                <p>
                    Die neue Konfiguration wird erst
                    nach einem korrekten Code aktiviert.
                </p>

                <form wire:submit="confirmTotp">
                    <div class="field">
                        <label for="totpCode">
                            Aktueller 6-stelliger Code
                        </label>

                        <input
                            id="totpCode"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            autocomplete="one-time-code"
                            wire:model="totpCode"
                            required
                        >

                        @error('totpCode')
                            <p role="alert">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button type="submit">
                        TOTP bestätigen und aktivieren
                    </button>
                </form>
            </div>
        @endif
    </section>

    <section>
        <h2>Recovery Codes</h2>

        @if ($recoveryCodes !== [])
            <p role="alert">
                Diese vier Codes werden nur jetzt
                im Klartext angezeigt. Bitte sicher
                außerhalb des Portals speichern.
            </p>

            <ol>
                @foreach ($recoveryCodes as $recoveryCode)
                    <li>
                        <code>
                            {{ $recoveryCode }}
                        </code>
                    </li>
                @endforeach
            </ol>

            <button
                type="button"
                wire:click="hideRecoveryCodes"
            >
                Codes ausblenden
            </button>
        @else
            @if ($hasRecoveryCodes)
                <p>
                    Für dieses Konto sind Recovery Codes
                    hinterlegt. Aus Sicherheitsgründen können
                    sie nicht erneut angezeigt werden.
                </p>
            @else
                <p>
                    Derzeit sind keine nutzbaren
                    Recovery Codes hinterlegt.
                </p>
            @endif

            @if (
                $twoFactorRequired
                || $emailActive
                || $totpActive
            )
                <button
                    type="button"
                    wire:click="regenerateRecoveryCodes"
                    wire:confirm="Neue Recovery Codes erzeugen? Alle bisherigen ungenutzten Codes werden dadurch sofort ungültig."
                >
                    Neue Recovery Codes erzeugen
                </button>
            @endif
        @endif
    </section>
</div>