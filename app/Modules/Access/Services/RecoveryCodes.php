<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Models\TwoFactorRecoveryCode;
use App\Modules\Access\Models\User;

/**
 * Wiederherstellungscodes für den Fall, dass der zweite Faktor nicht verfügbar ist. Jeder Code gilt einmal.
 * Im Klartext gibt es sie nur bei der Erzeugung; gespeichert wird der Hash (siehe CodeHasher).
 */
final class RecoveryCodes
{
    public const COUNT = 10;

    /** Ohne leicht verwechselbare Zeichen (0/O, 1/I/L). */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Ersetzt alle unbenutzten Codes durch neue.
     *
     * @return list<string> die neuen Codes im Klartext, Format XXXXX-XXXXX
     */
    public function generate(User $user): array
    {
        $this->invalidateAll($user);

        $codes = [];

        for ($i = 0; $i < self::COUNT; $i++) {
            $code = $this->randomCode();
            $codes[] = $code;

            $user->twoFactorRecoveryCodes()->create(['code_hash' => CodeHasher::hash($code)]);
        }

        return $codes;
    }

    /** Löst einen Code ein; er gilt danach als benutzt. */
    public function consume(User $user, string $code): bool
    {
        if (CodeHasher::normalize($code) === '') {
            return false;
        }

        $recovery = $user->twoFactorRecoveryCodes()
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->where('code_hash', CodeHasher::hash($code))
            ->first();

        if (! $recovery instanceof TwoFactorRecoveryCode) {
            return false;
        }

        $recovery->forceFill(['used_at' => now()])->save();

        return true;
    }

    public function remaining(User $user): int
    {
        return $user->twoFactorRecoveryCodes()->whereNull('used_at')->whereNull('invalidated_at')->count();
    }

    public function invalidateAll(User $user): void
    {
        $user->twoFactorRecoveryCodes()
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->update(['invalidated_at' => now()]);
    }

    private function randomCode(): string
    {
        $characters = '';

        for ($i = 0; $i < 10; $i++) {
            $characters .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return substr($characters, 0, 5).'-'.substr($characters, 5);
    }
}
