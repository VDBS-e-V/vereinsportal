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

<div class="card">
    <h1>Passwort vergessen</h1>

    @if ($submitted)
        <p role="status">
            Falls ein nutzbares Konto zu dieser
            E-Mail-Adresse existiert, wurde eine
            E-Mail zum Zurücksetzen des Passworts
            vorbereitet.
        </p>
    @endif

    <form wire:submit="requestReset">
        <div class="field">
            <label for="email">
                E-Mail-Adresse
            </label>

            <input
                id="email"
                name="email"
                type="email"
                wire:model="email"
                autocomplete="email"
                required
            >

            @error('email')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="requestReset"
        >
            Reset-Link anfordern
        </button>
    </form>
</div>