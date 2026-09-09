<?php

use App\Modules\Identity\Actions\Auth\ResetPasswordAction;
use App\Modules\Identity\Exceptions\PasswordResetFailed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query(
            'email',
            ''
        );
    }

    public function resetPassword(
        ResetPasswordAction $resetPassword,
    ): void {
        $this->resetErrorBag();

        try {
            $resetPassword->execute(
                email: $this->email,
                token: $this->token,
                password: $this->password,
                passwordConfirmation:
                    $this->password_confirmation,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            );
        } catch (PasswordResetFailed $exception) {
            $this->password = '';
            $this->password_confirmation = '';

            $this->addError(
                'reset',
                $exception->getMessage(),
            );

            return;
        }

        $this->password = '';
        $this->password_confirmation = '';

        session()->flash(
            'status',
            'Ihr Passwort wurde geändert. Sie können sich jetzt neu anmelden.'
        );

        $this->redirectRoute(
            'my.login',
            navigate: false,
        );
    }
};

?>

<div class="portal-page portal-page--small">
    <header class="portal-page__header">
        <h1>Neues Passwort</h1>
        <p class="portal-page__lead">
            Legen Sie ein neues Passwort für Ihr Benutzerkonto fest.
        </p>
    </header>

    @error('reset')
        <x-vdbs.notice type="danger" role="alert">
            {{ $message }}
        </x-vdbs.notice>
    @enderror

    <form class="portal-page__form form" wire:submit="resetPassword">
        <div class="form__field">
            <label class="form__label" for="email">
                E-Mail-Adresse
            </label>

            <input
                class="form__control"
                id="email"
                type="email"
                wire:model="email"
                readonly
            >
        </div>

        <div class="form__field">
            <label class="form__label" for="password">
                Neues Passwort
            </label>

            <input
                class="form__control"
                id="password"
                type="password"
                wire:model="password"
                autocomplete="new-password"
                required
                @error('password')
                    aria-invalid="true"
                    aria-describedby="password-reset-password-error"
                @enderror
            >

            @error('password')
                <p class="form__error" id="password-reset-password-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="form__field">
            <label class="form__label" for="password_confirmation">
                Neues Passwort wiederholen
            </label>

            <input
                class="form__control"
                id="password_confirmation"
                type="password"
                wire:model="password_confirmation"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="portal-page__actions">
            <button class="btn" type="submit">
                Passwort speichern
            </button>
        </div>
    </form>
</div>
