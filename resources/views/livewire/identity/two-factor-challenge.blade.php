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

<div class="card">
    <h1>Zwei-Faktor-Anmeldung</h1>

    <p>
        Bitte bestätigen Sie die Anmeldung
        mit einem zweiten Faktor.
    </p>

    @if ($challengeError !== null)
        <p role="alert">
            {{ $challengeError }}
        </p>
    @endif

    @if ($emailAvailable)
        <section>
            <h2>E-Mail-Code</h2>

            <button type="button" wire:click="sendEmailCode">
                Code per E-Mail senden
            </button>

            @if ($emailSent)
                <p role="status">
                    Der Sicherheitscode wurde
                    zum Versand vorbereitet.
                </p>
            @endif

            <form wire:submit="verifyEmail">
                <div class="field">
                    <label for="emailCode">
                        E-Mail-Code
                    </label>

                    <input id="emailCode" type="text" inputmode="numeric" maxlength="6" wire:model="emailCode"
                        autocomplete="one-time-code">
                </div>

                <button type="submit">
                    E-Mail-Code prüfen
                </button>
            </form>
        </section>
    @endif

    @if ($totpAvailable)
        <section>
            <h2>Authenticator-App</h2>

            <form wire:submit="verifyTotp">
                <div class="field">
                    <label for="totpCode">
                        TOTP-Code
                    </label>

                    <input id="totpCode" type="text" inputmode="numeric" maxlength="6" wire:model="totpCode"
                        autocomplete="one-time-code">
                </div>

                <button type="submit">
                    TOTP-Code prüfen
                </button>
            </form>
        </section>
    @endif

    @if ($recoveryAvailable)
        <section>
            <h2>Recovery Code</h2>

            <form wire:submit="verifyRecovery">
                <div class="field">
                    <label for="recoveryCode">
                        Recovery Code
                    </label>

                    <input id="recoveryCode" type="text" wire:model="recoveryCode" autocomplete="off">
                </div>

                <button type="submit">
                    Recovery Code verwenden
                </button>
            </form>
        </section>
    @endif
</div>