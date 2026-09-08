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

<div class="portal-page portal-page--small">
    <header class="portal-page__header">
        <h1>Anmelden</h1>
    </header>

    @if (session('status'))
        <p class="notice notice--success" role="status">
            {{ session('status') }}
        </p>
    @endif

    @if ($loginError !== null)
        <p class="notice notice--danger" role="alert">
            {{ $loginError }}
        </p>
    @endif

    <form class="portal-page__form" wire:submit="login">
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

        <div class="field field--choice">
            <label>
                <input type="checkbox" wire:model="remember">
                <span>Angemeldet bleiben</span>
            </label>
        </div>

        <div class="portal-page__actions">
            <button type="submit" wire:loading.attr="disabled" wire:target="login">
                Anmelden
            </button>
        </div>
    </form>

    <div class="portal-page__links">
        <a href="{{ route('my.password.request') }}">
            Passwort vergessen?
        </a>
    </div>
</div>
