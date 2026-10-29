<?php

namespace App\Modules\Membership\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Membership\Mail\ApplicationNoticeMail;
use App\Modules\Membership\Mail\VerifyApplicantEmailMail;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Online-Antrag auf Mitgliedschaft: legt Person, Konto (Status „eingeladen“ bis zur Bestätigung der E-Mail) und einen Antrag
 * an. Das Konto darf sich nach der Bestätigung anmelden, nutzt aber nur den Bereich Konto; die Rolle Vereinsmitglied
 * vergibt erst die Aufnahme durch den Vorstand. Gibt es die E-Mail-Adresse oder die Person schon, wird nichts angelegt;
 * der Antragsteller sieht davon nichts, die Adresse erhält eine neutrale Nachricht (keine Auskunft über Mitglieder).
 */
final class OnlineApplications
{
    public const VERIFY_HOURS = 48;

    public function __construct(private readonly Memberships $memberships) {}

    /**
     * @param  array{first_name: string, last_name: string, birth_date: string, email: string, password: string}  $data
     */
    public function apply(array $data): void
    {
        $email = mb_strtolower(trim($data['email']));

        if ($this->isDuplicate($data['first_name'], $data['last_name'], $data['birth_date'], $email)) {
            $this->notifyDuplicate($email);

            return;
        }

        $user = DB::transaction(function () use ($data, $email): User {
            $person = Person::query()->create([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'birth_date' => $data['birth_date'],
                'email' => $email,
                'country_code' => 'DE',
            ]);

            $user = User::query()->create([
                'person_id' => $person->id,
                'email' => $email,
                'password' => $data['password'],
                'status' => UserStatus::PendingVerification,
            ]);

            $this->memberships->requestOnline($person);

            return $user;
        });

        AuditLog::record(AuditEvent::UserCreated, null, $user, ['selbstregistrierung' => true]);

        $this->sendVerification($user);
    }

    /** Aktiviert das Konto, wenn der signierte Link stimmt (Aufruf nach Prüfung der Signatur). */
    public function verify(User $user, string $hash): bool
    {
        if (! hash_equals(sha1($user->email), $hash) || $user->status === UserStatus::Disabled) {
            return false;
        }

        if ($user->status === UserStatus::PendingVerification) {
            $user->forceFill(['status' => UserStatus::Active, 'email_verified_at' => now()])->save();
        }

        return true;
    }

    /** Sendet den Bestätigungslink erneut, nur an Konten, die noch nicht bestätigt sind. Die Antwort an den Besucher ist immer gleich. */
    public function resend(string $email): void
    {
        $user = User::query()->where('email', mb_strtolower(trim($email)))->where('status', UserStatus::PendingVerification->value)->first();

        if ($user instanceof User) {
            $this->sendVerification($user);
        }
    }

    private function sendVerification(User $user): void
    {
        $url = URL::temporarySignedRoute('join.verify', now()->addHours(self::VERIFY_HOURS), ['user' => $user->id, 'hash' => sha1($user->email)]);

        Mail::to($user->email)->send(new VerifyApplicantEmailMail($url, $user->person?->fullName() ?: $user->email, self::VERIFY_HOURS));
    }

    private function isDuplicate(string $first, string $last, string $birth, string $email): bool
    {
        return User::query()->where('email', $email)->exists()
            || Person::query()->where('email', $email)->exists()
            || Membership::query()->where('email', $email)->exists()
            || $this->memberships->hasOpenDuplicate($first, $last, Carbon::parse($birth), null)
            || Person::query()->where('first_name', trim($first))->where('last_name', trim($last))->whereDate('birth_date', $birth)->whereHas('user')->exists();
    }

    private function notifyDuplicate(string $email): void
    {
        $account = User::query()->where('email', $email)->first();

        Mail::to($email)->send(new ApplicationNoticeMail(
            $account instanceof User,
            route('login'),
            route('password.request'),
            route('portal.contact'),
        ));
    }
}
