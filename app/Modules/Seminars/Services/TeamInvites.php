<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Mail\ApplicationConfirmationMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\SeminarTeamInvite;
use DomainException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Allgemeiner Bewerbungslink je Seminar (Zweitweg für Personen ohne persönlichen Zugang). Der Link gilt bis zum Stichtag.
 * Bewerbungen darüber zählen erst nach dem Klick auf den Bestätigungslink in der E-Mail (Schutz vor fremden Adressen und Spam).
 */
final class TeamInvites
{
    public function __construct(private readonly TeamApplications $team) {}

    /**
     * Erzeugt einen neuen Link; ältere Links des Seminars werden gesperrt. Der Klartext steht nur in dieser Antwort.
     *
     * @return array{invite: SeminarTeamInvite, link: string}
     */
    public function create(Seminar $seminar, User $by): array
    {
        $seminar->teamInvites()->valid()->update(['revoked_at' => now()]);

        $token = Str::random(40);
        $invite = $seminar->teamInvites()->create([
            'token_hash' => hash('sha256', $token),
            'created_by_user_id' => $by->id,
            'expires_at' => $seminar->application_deadline?->copy()->endOfDay()->addDay(),
        ]);

        AuditLog::record(AuditEvent::SeminarUpdated, $by, $seminar, ['felder' => ['Bewerbungslink erzeugt']]);

        return ['invite' => $invite, 'link' => route('team-invite.show', ['token' => $token])];
    }

    public function revoke(Seminar $seminar, User $by): int
    {
        $count = $seminar->teamInvites()->valid()->update(['revoked_at' => now()]);

        if ($count > 0) {
            AuditLog::record(AuditEvent::SeminarUpdated, $by, $seminar, ['felder' => ['Bewerbungslink gesperrt']]);
        }

        return $count;
    }

    /** Das Seminar zu einem gültigen Link, sonst null (auch wenn Stichtag vorbei oder Seminar nicht mehr offen). */
    public function seminarFor(string $token): ?Seminar
    {
        $invite = SeminarTeamInvite::query()->valid()->where('token_hash', hash('sha256', $token))->with('seminar.partner', 'seminar.sessions')->first();

        if ($invite === null || $invite->seminar === null || ! $this->team->isOpen($invite->seminar)) {
            return null;
        }

        return $invite->seminar;
    }

    /**
     * Nimmt eine Bewerbung über den Link an und schickt die Bestätigungsmail. Die Antwort an die Person ist immer dieselbe.
     *
     * @param  array{email: string, name: string, phone?: ?string, birth_year?: ?int, competences?: list<string>, experience?: ?string, note?: ?string, wants_lead?: bool, consent_contact?: bool}  $details
     *
     * @throws DomainException wenn das Seminar nicht (mehr) offen ist
     */
    public function apply(Seminar $seminar, array $details): void
    {
        if (! $this->team->isOpen($seminar)) {
            throw new DomainException('Für dieses Seminar lässt sich kein Interesse mehr bekunden.');
        }

        $email = mb_strtolower(trim($details['email']));
        $contact = TeamContact::query()->where('email', $email)->first() ?? TeamContact::query()->create(['email' => $email, 'origin' => 'link']);
        $application = $seminar->applications()->where('contact_id', $contact->id)->first();

        if ($application instanceof SeminarApplication && $application->status !== ApplicationStatus::Withdrawn) {
            // Schon bestätigt oder entschieden: nichts doppelt anlegen. Noch unbestätigt: die Bestätigungsmail erneut schicken. Die Antwort an die Person bleibt gleich.
            if ($application->confirmed_at === null) {
                $this->sendConfirmation($application);
            }

            return;
        }

        $application = $this->team->applyContact($seminar, $contact, $details + ['name' => $details['name']], confirmed: false);

        $this->sendConfirmation($application);
    }

    public function sendConfirmation(SeminarApplication $application): void
    {
        $contact = $application->contact;

        if ($contact === null || app(MailSuppressions::class)->isSuppressed($contact->email)) {
            return;
        }

        $url = URL::temporarySignedRoute('team-invite.confirm', now()->addDay(), ['application' => $application->id]);

        Mail::to($contact->email)->send(new ApplicationConfirmationMail($application->seminar->loadMissing('partner', 'sessions'), $application->applicantName(), $url));
    }

    /** Bestätigt die Adresse; die Bewerbung zählt ab jetzt. */
    public function confirm(SeminarApplication $application): bool
    {
        if ($application->confirmed_at !== null || $application->status === ApplicationStatus::Withdrawn) {
            return false;
        }

        $application->forceFill(['confirmed_at' => now()])->save();

        return true;
    }
}
