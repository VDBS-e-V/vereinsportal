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

<div class="portal-page portal-page--small">
    <header class="portal-page__header">
        <h1>Passwort ändern</h1>
    </header>

    @if ($saved)
        <x-vdbs.notice type="success" role="status">
            Ihr Passwort wurde geändert.
        </x-vdbs.notice>
    @endif

    <form class="portal-page__form form" wire:submit="changePassword">
        <div class="form__field">
            <label class="form__label" for="current_password">
                Aktuelles Passwort
            </label>

            <input
                class="form__control"
                id="current_password"
                type="password"
                wire:model="current_password"
                autocomplete="current-password"
                required
                @error('current_password')
                    aria-invalid="true"
                    aria-describedby="password-change-current-error"
                @enderror
            >

            @error('current_password')
                <p class="form__error" id="password-change-current-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
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
                    aria-describedby="password-change-new-error"
                @enderror
            >

            @error('password')
                <p class="form__error" id="password-change-new-error" role="alert">
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
            <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="changePassword">
                Passwort ändern
            </button>
        </div>
    </form>
</div>
