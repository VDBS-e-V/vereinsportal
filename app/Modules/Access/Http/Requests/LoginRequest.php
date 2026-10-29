<?php

namespace App\Modules\Access\Http\Requests;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
        ];
    }

    /**
     * Prüft die Anmeldedaten und gibt das Konto zurück, ohne es anzumelden: ob die Anmeldung sofort erfolgt
     * oder erst nach dem zweiten Faktor, entscheidet der Controller. Nur aktive Konten dürfen sich anmelden.
     * Fehlerhafte Versuche sind pro E-Mail und IP begrenzt, die Fehlermeldung verrät nie, ob ein Konto existiert.
     *
     * @throws ValidationException
     */
    public function validatedUser(): User
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'email' => $this->string('email')->toString(),
            'password' => $this->string('password')->toString(),
            'status' => UserStatus::Active->value,
        ];

        $provider = Auth::guard('web')->getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        if (! $user instanceof User || ! $provider->validateCredentials($user, $credentials)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Die Anmeldedaten sind nicht korrekt oder das Konto ist nicht aktiv.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'Zu viele Anmeldeversuche. Bitte versuchen Sie es in '.ceil($seconds / 60).' Minute(n) erneut.',
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')->toString()).'|'.$this->ip());
    }
}
