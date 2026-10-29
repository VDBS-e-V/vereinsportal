<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Models\TwoFactorMethod;
use App\Modules\Access\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Zwei-Faktor-Anmeldung eines Nutzers: Methoden einrichten und entfernen, Codes prüfen.
 *
 * Methoden: Authenticator-App (TOTP) und E-Mail-Code. Eine Methode gilt erst nach der Bestätigung mit einem
 * gültigen Code (confirmed_at) und bis zum Entfernen (disabled_at). Wiederherstellungscodes entstehen mit der
 * ersten Methode und verfallen, wenn die letzte entfernt wird.
 */
final class TwoFactorManager
{
    public function __construct(
        private readonly RecoveryCodes $recoveryCodes,
        private readonly EmailChallenges $emailChallenges,
    ) {}

    /** Bestätigte, nicht entfernte Methoden. */
    public function activeMethods(User $user): \Illuminate\Support\Collection
    {
        return $user->twoFactorMethods()->whereNotNull('confirmed_at')->whereNull('disabled_at')->get();
    }

    public function isEnabled(User $user): bool
    {
        return $user->twoFactorMethods()->whereNotNull('confirmed_at')->whereNull('disabled_at')->exists();
    }

    public function has(User $user, TwoFactorMethodType $type): bool
    {
        return $user->twoFactorMethods()->where('type', $type->value)->whereNotNull('confirmed_at')->whereNull('disabled_at')->exists();
    }

    /** Die Methode, mit der die Anmeldung beginnt: die bevorzugte, sonst App vor E-Mail. */
    public function defaultType(User $user): ?TwoFactorMethodType
    {
        $preferred = $user->preferred_two_factor_method;

        if ($preferred instanceof TwoFactorMethodType && $this->has($user, $preferred)) {
            return $preferred;
        }

        foreach ([TwoFactorMethodType::Totp, TwoFactorMethodType::Email] as $type) {
            if ($this->has($user, $type)) {
                return $type;
            }
        }

        return null;
    }

    /** Die noch nicht bestätigte App-Einrichtung; sie wird bei erneutem Aufruf der Seite wiederverwendet. */
    public function pendingTotp(User $user): ?TwoFactorMethod
    {
        return $user->twoFactorMethods()
            ->where('type', TwoFactorMethodType::Totp->value)
            ->whereNull('confirmed_at')
            ->latest('id')
            ->first();
    }

    /** Beginnt die Einrichtung der App mit einem neuen Secret (ein bisheriger unbestätigter Versuch entfällt). */
    public function beginTotp(User $user): TwoFactorMethod
    {
        $user->twoFactorMethods()
            ->where('type', TwoFactorMethodType::Totp->value)
            ->whereNull('confirmed_at')
            ->delete();

        return $user->twoFactorMethods()->create([
            'type' => TwoFactorMethodType::Totp,
            'secret' => Totp::generateSecret(),
        ]);
    }

    /**
     * Bestätigt die App mit dem ersten Code.
     *
     * @return list<string>|null null bei falschem Code; sonst die neuen Wiederherstellungscodes (leer, wenn es schon welche gibt)
     */
    public function confirmTotp(User $user, string $code): ?array
    {
        $pending = $this->pendingTotp($user);

        if (! $pending instanceof TwoFactorMethod || Totp::verify((string) $pending->secret, $code) === null) {
            return null;
        }

        return $this->activate($user, $pending);
    }

    /**
     * Bestätigt die E-Mail-Methode mit dem zugesandten Code.
     *
     * @return list<string>|null null bei falschem Code; sonst die neuen Wiederherstellungscodes (leer, wenn es schon welche gibt)
     */
    public function confirmEmail(User $user, string $code): ?array
    {
        if (! $this->emailChallenges->verify($user, $code)) {
            return null;
        }

        $method = $user->twoFactorMethods()
            ->where('type', TwoFactorMethodType::Email->value)
            ->whereNull('disabled_at')
            ->first() ?? $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Email]);

        return $this->activate($user, $method);
    }

    /** Entfernt eine Methode; mit der letzten verfallen auch die Wiederherstellungscodes. */
    public function disable(User $user, TwoFactorMethodType $type): void
    {
        $user->twoFactorMethods()
            ->where('type', $type->value)
            ->whereNull('disabled_at')
            ->update(['disabled_at' => now()]);

        if ($user->preferred_two_factor_method === $type) {
            $user->forceFill(['preferred_two_factor_method' => null])->save();
        }

        if (! $this->isEnabled($user)) {
            $this->recoveryCodes->invalidateAll($user);
            $user->forceFill(['preferred_two_factor_method' => null])->save();
        } elseif ($user->preferred_two_factor_method === null) {
            $user->forceFill(['preferred_two_factor_method' => $this->defaultType($user)])->save();
        }
    }

    /** Prüft den Code beim Anmelden. App-Codes gelten pro Zeitfenster nur einmal. */
    public function verify(User $user, TwoFactorMethodType $type, string $code): bool
    {
        return match ($type) {
            TwoFactorMethodType::Totp => $this->verifyTotp($user, $code),
            TwoFactorMethodType::Email => $this->has($user, TwoFactorMethodType::Email) && $this->emailChallenges->verify($user, $code),
        };
    }

    public function verifyRecovery(User $user, string $code): bool
    {
        return $this->isEnabled($user) && $this->recoveryCodes->consume($user, $code);
    }

    /** @return list<string> */
    public function regenerateRecoveryCodes(User $user): array
    {
        return $this->recoveryCodes->generate($user);
    }

    public function remainingRecoveryCodes(User $user): int
    {
        return $this->recoveryCodes->remaining($user);
    }

    private function verifyTotp(User $user, string $code): bool
    {
        foreach ($this->activeMethods($user)->where('type', TwoFactorMethodType::Totp) as $method) {
            $counter = Totp::verify((string) $method->secret, $code);

            if ($counter === null) {
                continue;
            }

            // Derselbe Code gilt nur einmal, auch innerhalb seines Zeitfensters.
            return Cache::add("two-factor:totp:{$user->id}:{$counter}", true, 120);
        }

        return false;
    }

    /** @return list<string> */
    private function activate(User $user, TwoFactorMethod $method): array
    {
        $method->forceFill(['confirmed_at' => now(), 'disabled_at' => null])->save();

        if ($user->preferred_two_factor_method === null) {
            $user->forceFill(['preferred_two_factor_method' => $method->type])->save();
        }

        return $this->recoveryCodes->remaining($user) === 0 ? $this->recoveryCodes->generate($user) : [];
    }
}
