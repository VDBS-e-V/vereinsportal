<?php

use App\Modules\Identity\Actions\Auth\AttemptLoginAction;
use App\Modules\Identity\Exceptions\LoginFailed;
use App\Modules\Identity\Support\PendingLogin;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public ?string $loginError = null;

    public function mount(
        PendingLogin $pendingLogin,
    ): void {
        if (Auth::check()) {
            $this->redirectRoute(
                'my.home',
                navigate: false,
            );

            return;
        }

        if ($pendingLogin->exists()) {
            $this->redirectRoute(
                'my.two-factor.challenge',
                navigate: false,
            );
        }
    }

    public function login(
        AttemptLoginAction $attemptLogin,
        PendingLogin $pendingLogin,
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
                password:
                $validated['password'],
                remember:
                $validated['remember'],
                ipAddress:
                request()->ip()
                ?? '0.0.0.0',
                userAgent:
                request()->userAgent(),
            );
        } catch (LoginFailed $exception) {
            $this->password = '';
            $this->loginError =
                $exception->getMessage();

            return;
        }

        $this->password = '';

        if ($pendingLogin->exists()) {
            $this->redirectRoute(
                'my.two-factor.challenge',
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
    <h1>Anmelden</h1>

    @if (session('status'))
        <p role="status">
            {{ session('status') }}
        </p>
    @endif

    @if ($loginError !== null)
        <p role="alert">
            {{ $loginError }}
        </p>
    @endif

    <form wire:submit="login">
        <div class="field">
            <label for="email">
                E-Mail-Adresse
            </label>

            <input id="email" type="email" wire:model="email" autocomplete="email" required>

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

            <input id="password" type="password" wire:model="password" autocomplete="current-password" required>

            @error('password')
                <p role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="field">
            <label>
                <input type="checkbox" wire:model="remember">
                Angemeldet bleiben
            </label>
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="login">
            Anmelden
        </button>
    </form>

    <p>
        <a href="{{ route('my.password.request') }}">
            Passwort vergessen?
        </a>
    </p>
</div>