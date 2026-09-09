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
        <p class="portal-page__lead">
            Melden Sie sich mit Ihrer E-Mail-Adresse und Ihrem Passwort an.
        </p>
    </header>

    @if (session('status'))
        @php
            $statusType = session('status_type', 'success');
            $statusRole = in_array(
                $statusType,
                ['warning', 'danger'],
                true,
            ) ? 'alert' : 'status';
        @endphp

        <x-vdbs.notice :type="$statusType" :role="$statusRole">
            {{ session('status') }}
        </x-vdbs.notice>
    @endif

    @if ($loginError !== null)
        <x-vdbs.notice type="danger" role="alert">
            {{ $loginError }}
        </x-vdbs.notice>
    @endif

    <form class="portal-page__form form" wire:submit="login">
        <div class="form__field">
            <label class="form__label" for="email">
                E-Mail-Adresse
            </label>

            <input
                class="form__control"
                id="email"
                type="email"
                wire:model="email"
                autocomplete="email"
                required
                @error('email')
                    aria-invalid="true"
                    aria-describedby="login-email-error"
                @enderror
            >

            @error('email')
                <p class="form__error" id="login-email-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="form__field">
            <label class="form__label" for="password">
                Passwort
            </label>

            <input
                class="form__control"
                id="password"
                type="password"
                wire:model="password"
                autocomplete="current-password"
                required
                @error('password')
                    aria-invalid="true"
                    aria-describedby="login-password-error"
                @enderror
            >

            @error('password')
                <p class="form__error" id="login-password-error" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="form__choice">
            <input id="remember" type="checkbox" wire:model="remember">
            <label for="remember">Angemeldet bleiben</label>
        </div>

        <div class="portal-page__actions">
            <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="login">
                Anmelden
            </button>
        </div>
    </form>

    <div class="portal-page__links">
        <a href="{{ route('my.registration.create') }}">
            Noch kein Konto? Registrieren
        </a>

        <a href="{{ route('my.password.request') }}">
            Passwort vergessen?
        </a>
    </div>
</div>
