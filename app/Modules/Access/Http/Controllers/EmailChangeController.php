<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\EmailChangeRequestStatus;
use App\Modules\Access\Http\Middleware\EnsureSessionVersion;
use App\Modules\Access\Mail\EmailChangeConfirmMail;
use App\Modules\Access\Mail\EmailChangeNoticeMail;
use App\Modules\Access\Models\EmailChangeRequest;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Konto > E-Mail-Adresse. Eine Änderung gilt erst, wenn die NEUE Adresse sie über einen signierten Link bestätigt
 * (24 Stunden gültig, nur angemeldet nutzbar); die alte Adresse erhält einen Hinweis. Ein neuer Antrag ersetzt den alten.
 */
class EmailChangeController extends Controller
{
    private const VALID_HOURS = 24;

    private const MAX_REQUESTS_PER_HOUR = 3;

    public function edit(Request $request): View
    {
        $user = $this->user($request);

        return view('account.email', [
            'email' => $user->email,
            'pending' => $user->emailChangeRequests()
                ->where('status', EmailChangeRequestStatus::Pending->value)
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user($request);

        $data = $request->validate([
            'new_email' => [
                'required', 'string', 'email', 'max:254',
                Rule::unique('users', 'email'),
                Rule::unique('persons', 'email')->ignore($user->person_id),
            ],
            'password' => ['required', 'current_password'],
        ], [
            'new_email.required' => 'Bitte geben Sie die neue E-Mail-Adresse ein.',
            'new_email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'new_email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
            'password.current_password' => 'Das Passwort ist nicht korrekt.',
        ]);

        $newEmail = mb_strtolower(trim($data['new_email']));

        if ($newEmail === mb_strtolower($user->email)) {
            throw ValidationException::withMessages(['new_email' => 'Das ist bereits Ihre aktuelle E-Mail-Adresse.']);
        }

        $key = 'email-change|'.$user->id;

        if (RateLimiter::tooManyAttempts($key, self::MAX_REQUESTS_PER_HOUR)) {
            throw ValidationException::withMessages(['new_email' => 'Sie haben in kurzer Zeit zu viele Änderungen beantragt. Bitte versuchen Sie es später erneut.']);
        }

        RateLimiter::hit($key, 3600);

        $user->emailChangeRequests()
            ->where('status', EmailChangeRequestStatus::Pending->value)
            ->update(['status' => EmailChangeRequestStatus::Superseded->value, 'superseded_at' => now()]);

        $change = $user->emailChangeRequests()->create([
            'public_id' => (string) Str::ulid(),
            'old_email' => $user->email,
            'new_email' => $newEmail,
            'status' => EmailChangeRequestStatus::Pending,
            'expires_at' => now()->addHours(self::VALID_HOURS),
            'verification_sent_at' => now(),
        ]);

        $url = URL::temporarySignedRoute('account.email.confirm', $change->expires_at, ['publicId' => $change->public_id]);

        Mail::to($newEmail)->send(new EmailChangeConfirmMail($url, $newEmail, self::VALID_HOURS));
        Mail::to($user->email)->send(new EmailChangeNoticeMail($newEmail));

        return redirect()->route('account.email')->with('success', 'Wir haben einen Bestätigungslink an '.$newEmail.' gesendet.');
    }

    /** Bestätigung über den Link in der E-Mail (signiert, nur für das angemeldete Konto, das den Antrag gestellt hat). */
    public function confirm(Request $request, string $publicId): RedirectResponse
    {
        $user = $this->user($request);

        $change = $user->emailChangeRequests()
            ->where('public_id', $publicId)
            ->where('status', EmailChangeRequestStatus::Pending->value)
            ->where('expires_at', '>', now())
            ->first();

        if (! $change instanceof EmailChangeRequest) {
            return redirect()->route('account.email')->with('warning', 'Dieser Bestätigungslink ist nicht mehr gültig. Beantragen Sie die Änderung bei Bedarf erneut.');
        }

        $taken = User::query()->where('email', $change->new_email)->where('id', '!=', $user->id)->exists();

        if ($taken) {
            return redirect()->route('account.email')->with('warning', 'Diese E-Mail-Adresse wird inzwischen von einem anderen Konto verwendet.');
        }

        $oldEmail = $user->email;

        DB::transaction(function () use ($user, $change): void {
            $user->forceFill([
                'email' => $change->new_email,
                'email_verified_at' => now(),
                'session_version' => $user->session_version + 1,
            ])->save();

            $user->person?->forceFill(['email' => $change->new_email])->save();

            $change->forceFill(['status' => EmailChangeRequestStatus::Confirmed, 'confirmed_at' => now()])->save();

            $user->emailChangeRequests()
                ->where('status', EmailChangeRequestStatus::Pending->value)
                ->update(['status' => EmailChangeRequestStatus::Superseded->value, 'superseded_at' => now()]);
        });

        $request->session()->put(EnsureSessionVersion::KEY, (int) $user->session_version);
        AuditLog::record(AuditEvent::EmailChanged, $user, $user, ['alte_adresse' => $oldEmail, 'neue_adresse' => $change->new_email]);

        return redirect()->route('account.email')->with('success', 'Ihre E-Mail-Adresse wurde geändert. Alle anderen Sitzungen wurden abgemeldet.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
