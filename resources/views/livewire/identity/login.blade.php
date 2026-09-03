<?php

use App\Modules\Identity\Actions\Auth\AttemptLoginAction;
use App\Modules\Identity\Exceptions\LoginFailed;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public ?string $loginError = null;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirectRoute(
                'my.home',
                navigate: false,
            );
        }
    }

    public function login(
        AttemptLoginAction $attemptLogin,
    ): void {
        $validated = $this->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:254',
            ],
            'password' => [
                'required',
                'string',
            ],
            'remember' => [
                'boolean',
            ],
        ]);

        $this->loginError = null;

        try {
            $attemptLogin->execute(
                email: $validated['email'],
                password: $validated['password'],
                remember: $validated['remember'],
                ipAddress: request()->ip() ?? '0.0.0.0',
                userAgent: request()->userAgent(),
            );
        } catch (LoginFailed $exception) {
            $this->password = '';
            $this->loginError = $exception->getMessage();

            return;
        }

        $this->password = '';

        $this->redirectRoute(
            'my.home',
            navigate: false,
        );
    }
};

?>

<div class="card">
    <h1>Anmelden</h1>
    @if (session('status'))
        <p role="status">
            {{ session('status') }}
        </p>
    @endif

    @if ($loginError)
        <p role="alert">
            {{ $loginError }}
        </p>
    @endif

    <form wire:submit="login">
        <div class="field">
            <label for="email">
                E-Mail-Adresse
            </label>

            <input id="email" name="email" type="email" wire:model="email" autocomplete="email" required>

            @error('email')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="field">
            <label for="password">
                Passwort
            </label>

            <input id="password" name="password" type="password" wire:model="password" autocomplete="current-password"
                required>

            @error('password')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="field">
            <label>
                <input name="remember" type="checkbox" wire:model="remember">

                Angemeldet bleiben
            </label>
        </div>

        <p>
            <a href="{{ route('my.password.request') }}">
                Passwort vergessen?
            </a>
        </p>

        <button type="submit" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">
                Anmelden
            </span>

            <span wire:loading wire:target="login">
                Anmeldung wird geprüft …
            </span>
        </button>
    </form>
</div>