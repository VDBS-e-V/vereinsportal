<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Mail\SchoolMessageMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Support\CoordinationRecipients;
use DomainException;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Was Schulen im Portal tun können: ein Seminar anfragen, Termin und Kostenvoranschlag bestätigen und Rückmeldungen schicken.
 * Alles steht im Verlauf des Seminars; die Koordination bekommt eine Mail ohne Inhalt der Nachricht.
 */
final class SchoolRequests
{
    public function __construct(private readonly SeminarAdministration $seminars) {}

    /** @param  array{type: string, wished_period?: ?string, expected_participants?: mixed, grade_level?: ?string, notes?: ?string}  $data */
    public function request(PartnerUser $account, array $data): Seminar
    {
        $type = SeminarType::from($data['type']);
        $partner = $account->partner;

        $seminar = $this->seminars->create([
            'title' => $type->label().' – '.$partner->name,
            'type' => $type->value,
            'partner_id' => $partner->id,
            'grade_level' => $data['grade_level'] ?? null,
            'expected_participants' => $data['expected_participants'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], $account->user);

        $this->seminars->note($seminar, 'Anfrage über das Schulportal von '.$account->name.'. Wunschzeitraum: '.(filled($data['wished_period'] ?? null) ? trim($data['wished_period']) : 'keine Angabe'), $account->user);

        $this->notify('request', $seminar);

        return $seminar;
    }

    /**
     * Die Schule bestätigt Termin und Kostenvoranschlag. Möglich im Status „Terminiert“ mit mindestens einem Kostenvoranschlag, einmal je Seminar.
     *
     * @throws DomainException
     */
    public function confirm(Seminar $seminar, PartnerUser $account): void
    {
        if ($seminar->status !== SeminarStatus::Scheduled || ! $seminar->quotes()->exists()) {
            throw new DomainException('Dieses Seminar lässt sich zurzeit nicht bestätigen.');
        }

        if ($seminar->events()->where('type', SeminarEventType::SchoolConfirmed->value)->exists()) {
            throw new DomainException('Sie haben dieses Seminar schon bestätigt.');
        }

        $this->record($seminar, SeminarEventType::SchoolConfirmed, $account->user, 'Bestätigt von '.$account->name);
        $this->notify('confirmed', $seminar);
    }

    public function feedback(Seminar $seminar, PartnerUser $account, string $note): void
    {
        $this->record($seminar, SeminarEventType::SchoolNote, $account->user, trim($note));
        $this->notify('note', $seminar);
    }

    public function confirmed(Seminar $seminar): bool
    {
        return $seminar->events()->where('type', SeminarEventType::SchoolConfirmed->value)->exists();
    }

    private function record(Seminar $seminar, SeminarEventType $type, User $by, string $comment): void
    {
        $seminar->events()->create([
            'type' => $type,
            'occurred_at' => now(),
            'actor_user_id' => $by->id,
            'actor_label' => AuditLog::label($by),
            'comment' => $comment,
        ]);
    }

    private function notify(string $kind, Seminar $seminar): void
    {
        $seminar->loadMissing('partner');

        foreach (app(\App\Modules\Access\Services\NotificationPreferences::class)->filter(CoordinationRecipients::users(), 'school_requests') as $recipient) {
            try {
                Mail::to($recipient->email)->send(new SchoolMessageMail($kind, $seminar, route('seminars.show', $seminar)));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
