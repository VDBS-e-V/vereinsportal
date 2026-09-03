<?php

use App\Modules\Identity\Actions\Auth\ChangePasswordAction;
use App\Modules\Identity\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $saved = false;

    public function changePassword(
        ChangePasswordAction $changePassword,
    ): void {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $this->saved = false;

        $changePassword->execute(
            user: $user,
            currentPassword: $this->current_password,
            password: $this->password,
            passwordConfirmation:
                $this->password_confirmation,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        );

        $this->reset([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $this->saved = true;
    }
};

?>

<div class="card">
    <h1>Passwort ändern</h1>

    @if ($saved)
        <p role="status">
            Ihr Passwort wurde geändert.
        </p>
    @endif

    <form wire:submit="changePassword">
        <div class="field">
            <label for="current_password">
                Aktuelles Passwort
            </label>

            <input
                id="current_password"
                type="password"
                wire:model="current_password"
                autocomplete="current-password"
                required
            >

            @error('current_password')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
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
            Passwort ändern
        </button>
    </form>
</div>