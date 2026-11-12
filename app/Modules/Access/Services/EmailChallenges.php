<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Mail\TwoFactorCodeMail;
use App\Modules\Access\Models\TwoFactorEmailChallenge;
use App\Modules\Access\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Sicherheitscodes per E-Mail: sechs Ziffern, zehn Minuten gültig, einmal nutzbar. Ein neuer Code macht den
 * vorherigen ungültig; erneutes Senden ist auf einmal pro Minute begrenzt. Gespeichert wird nur der Hash.
 */
final class EmailChallenges
{
    public const VALID_MINUTES = 10;

    public const RESEND_SECONDS = 60;

    /** Sendet einen neuen Code. Gibt false zurück, wenn gerade erst einer gesendet wurde. */
    public function send(User $user): bool
    {
        if ($this->secondsUntilResend($user) > 0) {
            return false;
        }

        $user->twoFactorEmailChallenges()
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->update(['invalidated_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->twoFactorEmailChallenges()->create([
            'code_hash' => CodeHasher::hash($code),
            'expires_at' => now()->addMinutes(self::VALID_MINUTES),
            'sent_at' => now(),
        ]);

        Mail::to($user->email)->send(new TwoFactorCodeMail($code, self::VALID_MINUTES));

        return true;
    }

    /** Löst den zuletzt gesendeten Code ein, wenn er stimmt, noch gültig und unbenutzt ist. */
    public function verify(User $user, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($code) !== 6) {
            return false;
        }

        $challenge = $user->twoFactorEmailChallenges()
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $challenge instanceof TwoFactorEmailChallenge || ! hash_equals($challenge->code_hash, CodeHasher::hash($code))) {
            return false;
        }

        $challenge->forceFill(['used_at' => now()])->save();

        return true;
    }

    /** Sekunden, bis wieder gesendet werden darf (0 = jetzt). */
    public function secondsUntilResend(User $user): int
    {
        $latest = $user->twoFactorEmailChallenges()->latest('id')->first();

        if (! $latest instanceof TwoFactorEmailChallenge || $latest->sent_at === null) {
            return 0;
        }

        return max(0, (int) ceil(self::RESEND_SECONDS - $latest->sent_at->diffInSeconds(now(), true)));
    }
}
