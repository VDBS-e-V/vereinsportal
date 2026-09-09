<?php

use App\Modules\Identity\Actions\Auth\RequestPasswordResetAction;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public string $email = '';

    public bool $submitted = false;

    public function requestReset(
        RequestPasswordResetAction $requestPasswordReset,
    ): void {
        $validated = $this->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:254',
            ],
        ]);

        $requestPasswordReset->execute(
            email: $validated['email'],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        );

        $this->email = '';
        $this->submitted = true;
    }
};

?>

<div class="portal-page portal-page--small">
    <header class="portal-page__header">
        <h1>Passwort vergessen</h1>
        <p class="portal-page__lead">
            Fordern Sie einen zeitlich begrenzten Link zum Setzen eines neuen Passworts an.
        </p>
    </header>

    @if ($submitted)
        <x-vdbs.notice type="success" role="status">
            Falls ein nutzbares Konto zu dieser
            E-Mail-Adresse existiert, wurde eine
            E-Mail zum Zurücksetzen des Passworts
            vorbereitet.
        </x-vdbs.notice>
    @endif

    <form class="portal-page__form form" wire:submit="requestReset">
        <div class="form__field">
            <label class="form__label" for="email">
                E-Mail-Adresse
            </label>

            <input
                class="form__control"
                id="email"
                name="email"
                type="email"
                wire:model="email"
                autocomplete="email"
                required
                @error('email')
                    aria-invalid="true"
                    aria-describedby="password-forgot-email-error"
                @enderror
            >

            @error('email')
                <p class="form__error" id="password-forgot-email-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="portal-page__actions">
            <button
                class="btn"
                type="submit"
                wire:loading.attr="disabled"
                wire:target="requestReset"
            >
                Reset-Link anfordern
            </button>
        </div>
    </form>

    <div class="portal-page__links">
        <a href="{{ route('my.login') }}">
            Zurück zur Anmeldung
        </a>
    </div>
</div>
