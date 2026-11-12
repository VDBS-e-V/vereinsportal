<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\Contacts\Services\ContactAccessSender;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Mail\SeminarTeamInfoMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * „Team informieren“: eine gemeinsame Nachricht an alle, die im Team sind (Konto oder persönlicher Zugang), mit Seminar, Terminen und der Übersicht über das Team.
 * Kontaktdaten stehen in der Übersicht nur bei Personen, die der Weitergabe zugestimmt haben.
 */
final class TeamInfo
{
    /** @return Collection<int, SeminarApplication> */
    public function members(Seminar $seminar): Collection
    {
        return $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->with(['user.person', 'contact'])->orderBy('id')->get()
            ->reject(fn (SeminarApplication $application): bool => $application->isAnonymized())->values();
    }

    /** Kennung des aktuellen Teams, um „Team geändert“ zu erkennen. */
    public function key(Seminar $seminar): string
    {
        return sha1($this->members($seminar)->pluck('id')->sort()->implode(','));
    }

    public function changedSinceLastInfo(Seminar $seminar): bool
    {
        return $seminar->team_informed_at !== null && $seminar->team_informed_key !== $this->key($seminar);
    }

    /**
     * Übersicht über das Team für die Nachricht.
     *
     * @return list<array{name: string, email: ?string, phone: ?string}>
     */
    public function overview(Seminar $seminar): array
    {
        return $this->members($seminar)->map(fn (SeminarApplication $application): array => [
            'name' => $application->applicantName(),
            'email' => $application->isContact() && $application->guest_consent_contact ? $application->contact?->email : null,
            'phone' => $application->isContact() && $application->guest_consent_contact ? $application->guest_phone : null,
        ])->all();
    }

    /**
     * Sendet die Nachricht an die ausgewählten Teammitglieder (Bewerbungs-Nummern). Gibt die Zahl der Empfänger zurück.
     *
     * @param  list<int>  $applicationIds
     */
    public function send(Seminar $seminar, array $applicationIds, ?string $extraText): int
    {
        $seminar->loadMissing(['partner', 'sessions']);
        $overview = $this->overview($seminar);
        $sent = 0;

        foreach ($this->members($seminar)->whereIn('id', $applicationIds) as $application) {
            $email = $this->addressFor($application);

            if ($email === null) {
                continue;
            }

            try {
                Mail::to($email)->send(new SeminarTeamInfoMail($seminar, $application->applicantName(), $overview, filled($extraText) ? trim((string) $extraText) : null, $application->isContact() ? route('access.code') : route('seminar-applications.index')));
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($sent > 0) {
            $seminar->forceFill(['team_informed_at' => now(), 'team_informed_key' => $this->key($seminar)])->save();
            $seminar->events()->create(['type' => SeminarEventType::TeamInformed, 'occurred_at' => now(), 'comment' => $sent === 1 ? '1 Nachricht' : $sent.' Nachrichten']);
        }

        return $sent;
    }

    /** Adresse, an die das Teammitglied schreiben darf (nicht bei Sperre, Abmeldung oder abgeschalteter Benachrichtigung). */
    public function addressFor(SeminarApplication $application): ?string
    {
        if ($application->isContact()) {
            $contact = $application->contact;

            return $contact !== null && app(ContactAccessSender::class)->canMail($contact) ? $contact->email : null;
        }

        $user = $application->user;

        return $user !== null && app(NotificationPreferences::class)->allows($user, 'seminar_team') ? $user->email : null;
    }
}
