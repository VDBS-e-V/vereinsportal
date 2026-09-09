<?php

use App\Modules\Identity\Actions\Auth\FinalizeLoginAction;
use App\Modules\Identity\Actions\TwoFactor\IssueEmailTwoFactorChallengeAction;
use App\Modules\Identity\Actions\TwoFactor\UseRecoveryCodeAction;
use App\Modules\Identity\Actions\TwoFactor\VerifyEmailTwoFactorChallengeAction;
use App\Modules\Identity\Actions\TwoFactor\VerifyTotpChallengeAction;
use App\Modules\Identity\Exceptions\LoginFailed;
use App\Modules\Identity\Exceptions\TwoFactorChallengeFailed;
use App\Modules\Identity\Models\TwoFactorRecoveryCode;
use App\Modules\Identity\Support\PendingLogin;
use App\Modules\Identity\Support\TwoFactorRequirement;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public string $emailCode = '';

    public string $totpCode = '';

    public string $recoveryCode = '';

    public bool $emailAvailable = false;

    public bool $totpAvailable = false;

    public bool $recoveryAvailable = false;

    public bool $emailSent = false;

    public ?string $challengeError = null;

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

        $this->emailAvailable =
            $requirement->canUseEmail($user);

        $this->totpAvailable =
            $requirement->canUseTotp($user);

        $this->recoveryAvailable =
            TwoFactorRecoveryCode::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->whereNull('used_at')
                ->whereNull(
                    'invalidated_at'
                )
                ->exists();
    }

    public function sendEmailCode(
        PendingLogin $pendingLogin,
        IssueEmailTwoFactorChallengeAction $issue,
    ): void {
        $user = $pendingLogin->user();

        if ($user === null) {
            $this->redirectRoute(
                'my.login',
                navigate: false,
            );

            return;
        }

        $this->challengeError = null;

        try {
            $issue->execute($user);
        } catch (
            TwoFactorChallengeFailed $exception
        ) {
            $this->challengeError =
                $exception->getMessage();

            return;
        }

        $this->emailSent = true;
    }

    public function verifyEmail(
        PendingLogin $pendingLogin,
        VerifyEmailTwoFactorChallengeAction $verify,
        FinalizeLoginAction $finalize,
    ): void {
        $this->validate([
            'emailCode' => [
                'required',
                'digits:6',
            ],
        ]);

        $user = $pendingLogin->user();

        if ($user === null) {
            $this->redirectRoute(
                'my.login',
                navigate: false,
            );

            return;
        }

        try {
            $verify->execute(
                user: $user,
                code: $this->emailCode,
                ipAddress:
                request()->ip()
                ?? '0.0.0.0',
                userAgent:
                request()->userAgent(),
            );

            $this->finish(
                pendingLogin: $pendingLogin,
                finalize: $finalize,
                method:
                'password+email_2fa',
            );
        } catch (
            TwoFactorChallengeFailed $exception
        ) {
            $this->emailCode = '';
            $this->challengeError =
                $exception->getMessage();
        }
    }

    public function verifyTotp(
        PendingLogin $pendingLogin,
        VerifyTotpChallengeAction $verify,
        FinalizeLoginAction $finalize,
    ): void {
        $this->validate([
            'totpCode' => [
                'required',
                'digits:6',
            ],
        ]);

        $user = $pendingLogin->user();

        if ($user === null) {
            $this->redirectRoute(
                'my.login',
                navigate: false,
            );

            return;
        }

        try {
            $verify->execute(
                user: $user,
                code: $this->totpCode,
                ipAddress:
                request()->ip()
                ?? '0.0.0.0',
                userAgent:
                request()->userAgent(),
            );

            $this->finish(
                pendingLogin: $pendingLogin,
                finalize: $finalize,
                method:
                'password+totp',
            );
        } catch (
            TwoFactorChallengeFailed $exception
        ) {
            $this->totpCode = '';
            $this->challengeError =
                $exception->getMessage();
        }
    }

    public function verifyRecovery(
        PendingLogin $pendingLogin,
        UseRecoveryCodeAction $verify,
        FinalizeLoginAction $finalize,
    ): void {
        $this->validate([
            'recoveryCode' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $user = $pendingLogin->user();

        if ($user === null) {
            $this->redirectRoute(
                'my.login',
                navigate: false,
            );

            return;
        }

        try {
            $verify->execute(
                user: $user,
                code: $this->recoveryCode,
                ipAddress:
                request()->ip()
                ?? '0.0.0.0',
                userAgent:
                request()->userAgent(),
            );

            $this->finish(
                pendingLogin: $pendingLogin,
                finalize: $finalize,
                method:
                'password+recovery_code',
            );
        } catch (
            TwoFactorChallengeFailed $exception
        ) {
            $this->recoveryCode = '';
            $this->challengeError =
                $exception->getMessage();
        }
    }

    private function finish(
        PendingLogin $pendingLogin,
        FinalizeLoginAction $finalize,
        string $method,
    ): void {
        $data = $pendingLogin->data();
        $user = $pendingLogin->user();

        if (
            $data === null
            || $user === null
        ) {
            $pendingLogin->clear();

            $this->redirectRoute(
                'my.login',
                navigate: false,
            );

            return;
        }

        try {
            $finalize->execute(
                user: $user,
                remember: $data['remember'],
                method: $method,
                expectedSessionVersion:
                $data['session_version'],
                ipAddress:
                request()->ip()
                ?? '0.0.0.0',
                userAgent:
                request()->userAgent(),
            );
        } catch (LoginFailed) {
            $pendingLogin->clear();

            $this->redirectRoute(
                'my.login',
                navigate: false,
            );

            return;
        }

        $this->redirectRoute(
            'my.home',
            navigate: false,
        );
    }
};

?>

<div class="portal-page portal-page--small">
    <header class="portal-page__header">
        <h1>Zwei-Faktor-Anmeldung</h1>

        <p class="portal-page__lead">
            Bitte bestätigen Sie die Anmeldung
            mit einem zweiten Faktor.
        </p>
    </header>

    @if ($challengeError !== null)
        <x-vdbs.notice type="danger" role="alert">
            {{ $challengeError }}
        </x-vdbs.notice>
    @endif

    @if ($emailAvailable)
        <section class="portal-page__section">
            <div class="portal-page__section-header">
                <h2>E-Mail-Code</h2>
            </div>

            <div class="portal-page__actions">
                <button class="btn btn--secondary" type="button" wire:click="sendEmailCode">
                    Code per E-Mail senden
                </button>
            </div>

            @if ($emailSent)
                <x-vdbs.notice type="success" role="status">
                    Der Sicherheitscode wurde
                    zum Versand vorbereitet.
                </x-vdbs.notice>
            @endif

            <form class="portal-page__form form" wire:submit="verifyEmail">
                <div class="form__field">
                    <label class="form__label" for="emailCode">
                        E-Mail-Code
                    </label>

                    <input
                        class="form__control"
                        id="emailCode"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        wire:model="emailCode"
                        autocomplete="one-time-code"
                        required
                        @error('emailCode')
                            aria-invalid="true"
                            aria-describedby="two-factor-email-error"
                        @enderror
                    >

                    @error('emailCode')
                        <p class="form__error" id="two-factor-email-error" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="portal-page__actions">
                    <button class="btn" type="submit">
                        E-Mail-Code prüfen
                    </button>
                </div>
            </form>
        </section>
    @endif

    @if ($totpAvailable)
        <section class="portal-page__section">
            <div class="portal-page__section-header">
                <h2>Authenticator-App</h2>
            </div>

            <form class="portal-page__form form" wire:submit="verifyTotp">
                <div class="form__field">
                    <label class="form__label" for="totpCode">
                        TOTP-Code
                    </label>

                    <input
                        class="form__control"
                        id="totpCode"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        wire:model="totpCode"
                        autocomplete="one-time-code"
                        required
                        @error('totpCode')
                            aria-invalid="true"
                            aria-describedby="two-factor-totp-error"
                        @enderror
                    >

                    @error('totpCode')
                        <p class="form__error" id="two-factor-totp-error" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="portal-page__actions">
                    <button class="btn" type="submit">
                        TOTP-Code prüfen
                    </button>
                </div>
            </form>
        </section>
    @endif

    @if ($recoveryAvailable)
        <section class="portal-page__section">
            <div class="portal-page__section-header">
                <h2>Recovery Code</h2>
            </div>

            <form class="portal-page__form form" wire:submit="verifyRecovery">
                <div class="form__field">
                    <label class="form__label" for="recoveryCode">
                        Recovery Code
                    </label>

                    <input
                        class="form__control"
                        id="recoveryCode"
                        type="text"
                        wire:model="recoveryCode"
                        autocomplete="off"
                        required
                        @error('recoveryCode')
                            aria-invalid="true"
                            aria-describedby="two-factor-recovery-error"
                        @enderror
                    >

                    @error('recoveryCode')
                        <p class="form__error" id="two-factor-recovery-error" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="portal-page__actions">
                    <button class="btn" type="submit">
                        Recovery Code verwenden
                    </button>
                </div>
            </form>
        </section>
    @endif
</div>
