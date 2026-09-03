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

<div class="card">
    <h1>Neues Passwort</h1>

    @error('reset')
        <p role="alert">
            {{ $message }}
        </p>
    @enderror

    <form wire:submit="resetPassword">
        <div class="field">
            <label for="email">
                E-Mail-Adresse
            </label>

            <input
                id="email"
                type="email"
                wire:model="email"
                readonly
            >
        </div>

        <div class="field">
            <label for="password">
                Neues Passwort
            </label>

            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="new-password"
                required
            >

            @error('password')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">
                Neues Passwort wiederholen
            </label>

            <input
                id="password_confirmation"
                type="password"
                wire:model="password_confirmation"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit">
            Passwort speichern
        </button>
    </form>
</div>