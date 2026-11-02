<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Mailings\Mail\MailingMail;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Modules\Seminars\Models\Seminar;
use App\Support\Html\RichText;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Schreiben an Mitglieder (Vorstand, frei formuliert) und an Teamende (Koordination, auf der Vorlage der Geschäftsstelle mit eigenem Textabschnitt).
 * Jede Nachricht wird je Empfänger mit den Platzhaltern gefüllt. Gespeichert werden Betreff, Text mit Platzhaltern und die Zahl der Empfänger, nie die Empfängerliste.
 */
final class Mailings
{
    /** Werte, die als bereinigtes HTML eingesetzt werden und nicht maskiert werden dürfen. */
    private const RAW = ['eigener_text', 'termine'];

    public function __construct(private readonly MailRecipients $recipients, private readonly MailTemplates $templates) {}

    // ---------- Mitglieder ----------

    /**
     * Werte der Platzhalter aus den Angaben zur Versammlung.
     *
     * @param  array<string, mixed>  $meeting  title, date (Y-m-d), time (H:i), place, link, deadline (Y-m-d)
     * @return array<string, string>
     */
    public function meetingValues(array $meeting): array
    {
        $date = filled($meeting['date'] ?? null) ? Carbon::parse((string) $meeting['date'])->locale('de') : null;
        $time = filled($meeting['time'] ?? null) ? (string) $meeting['time'] : '';
        $deadline = filled($meeting['deadline'] ?? null) ? Carbon::parse((string) $meeting['deadline'])->format('d.m.Y') : '';

        return [
            'versammlung' => trim((string) ($meeting['title'] ?? '')),
            'termin' => $date !== null ? $date->isoFormat('dddd, DD.MM.YYYY').($time !== '' ? ' um '.$time.' Uhr' : '') : '',
            'datum' => $date?->format('d.m.Y') ?? '',
            'uhrzeit' => $time !== '' ? $time.' Uhr' : '',
            'ort' => trim((string) ($meeting['place'] ?? '')),
            'link' => trim((string) ($meeting['link'] ?? '')),
            'frist' => $deadline,
        ];
    }

    /**
     * Platzhalter der Versammlung, die im Text vorkommen, zu denen aber keine Angabe gemacht wurde.
     *
     * @param  array<string, mixed>  $meeting
     * @return list<string>
     */
    public function missingMeetingFields(string $subject, string $body, array $meeting): array
    {
        $values = $this->meetingValues($meeting);
        $used = array_unique([...MailPlaceholders::used($subject), ...MailPlaceholders::used($body)]);

        return array_values(array_filter($used, fn (string $key): bool => array_key_exists($key, MailPlaceholders::MEMBERS) && ($values[$key] ?? '') === ''));
    }

    /**
     * @param  array<string, mixed>  $meeting
     * @return array{subject: string, html: string}
     */
    public function previewMembers(string $subject, string $body, array $meeting, User $by): array
    {
        return $this->render($subject, $body, $this->meetingValues($meeting), $this->sample($by), $by);
    }

    /**
     * @param  array<string, mixed>  $meeting
     *
     * @throws DomainException
     */
    public function sendMembers(string $subject, string $body, array $meeting, User $by): Mailing
    {
        $list = $this->recipients->members();

        if ($list === []) {
            throw new DomainException('Es gibt keine Mitglieder mit E-Mail-Adresse.');
        }

        [$sent, $failed] = $this->deliver($list, $subject, $body, $this->meetingValues($meeting), $by, Mailing::KIND_MEMBERS);

        return $this->record(Mailing::KIND_MEMBERS, $subject, $body, null, null, 'members', $sent, $failed, $by);
    }

    public function sendMembersTest(string $subject, string $body, array $meeting, User $by): void
    {
        $this->deliver([$this->sample($by)], '[Test] '.$subject, $body, $this->meetingValues($meeting), $by, Mailing::KIND_MEMBERS);
    }

    // ---------- Teamende ----------

    /** Text der Vorlage mit dem eigenen Textabschnitt an der Stelle von {eigener_text}; ohne diese Stelle hinten angehängt. @return array{subject: string, body: string} */
    public function composeTeam(string $ownText): array
    {
        $template = $this->templates->get(MailTemplates::TEAM_SEMINAR);
        $own = RichText::clean($ownText) ?? '';
        $body = str_contains($template['body'], '{eigener_text}')
            ? $template['body']
            : $template['body'].($own !== '' ? '{eigener_text}' : '');

        return ['subject' => $template['subject'], 'body' => str_replace('{eigener_text}', $own, $body)];
    }

    /**
     * @return array{subject: string, html: string}
     */
    public function previewTeam(Seminar $seminar, string $ownText, User $by): array
    {
        $composed = $this->composeTeam($ownText);

        return $this->render($composed['subject'], $composed['body'], $this->seminarValues($seminar), $this->sample($by), $by);
    }

    /** @throws DomainException */
    public function sendTeam(Seminar $seminar, string $audience, string $ownText, User $by): Mailing
    {
        $list = $this->recipients->team($seminar, $audience);

        if ($list === []) {
            throw new DomainException('Für diese Auswahl gibt es keine Empfänger. Teamende, die diese Mitteilungen abbestellt haben, bekommen sie nicht.');
        }

        $composed = $this->composeTeam($ownText);
        [$sent, $failed] = $this->deliver($list, $composed['subject'], $composed['body'], $this->seminarValues($seminar), $by, Mailing::KIND_TEAM);

        return $this->record(Mailing::KIND_TEAM, $composed['subject'], $composed['body'], RichText::clean($ownText), $seminar->id, $audience, $sent, $failed, $by);
    }

    public function sendTeamTest(Seminar $seminar, string $ownText, User $by): void
    {
        $composed = $this->composeTeam($ownText);

        $this->deliver([$this->sample($by)], '[Test] '.$composed['subject'], $composed['body'], $this->seminarValues($seminar), $by, Mailing::KIND_TEAM);
    }

    /** Platzhalter aus den Angaben des Seminars. @return array<string, string> */
    public function seminarValues(Seminar $seminar): array
    {
        $seminar->loadMissing(['partner', 'sessions']);
        $sessions = $seminar->sessions->sortBy('starts_at')->values();
        $first = $sessions->first();

        $lines = $sessions->map(fn ($session): string => e($session->period()).($session->location ? ' – '.e($session->location) : ''))->all();

        return [
            'seminar' => (string) $seminar->title,
            'schule' => (string) ($seminar->partner?->name ?? ''),
            'termine' => $lines !== [] ? implode('<br>', $lines) : 'Die Termine folgen.',
            'erster_termin' => $first?->period() ?? '',
            'ort' => (string) ($first?->location ?? ''),
            'stichtag' => $seminar->application_deadline?->format('d.m.Y') ?? '',
            'link' => route('seminar-applications.index'),
        ];
    }

    // ---------- gemeinsam ----------

    /**
     * @param  array<string, string>  $values
     * @param  array{email: string, first: string, last: string}  $recipient
     * @return array{subject: string, html: string}
     */
    private function render(string $subject, string $body, array $values, array $recipient, User $by): array
    {
        $all = $this->personal($recipient, $by) + $values;

        return ['subject' => MailPlaceholders::render($subject, $all), 'html' => MailPlaceholders::render($body, $all, true, self::RAW)];
    }

    /**
     * @param  list<array{email: string, first: string, last: string}>  $list
     * @param  array<string, string>  $values
     * @return array{0: int, 1: int} gesendet, fehlgeschlagen
     */
    private function deliver(array $list, string $subject, string $body, array $values, User $by, string $kind): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($list as $recipient) {
            $rendered = $this->render($subject, $body, $values, $recipient, $by);

            try {
                Mail::to($recipient['email'])->send(new MailingMail($rendered['subject'], $rendered['html'], $kind));
                $sent++;
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
            }
        }

        return [$sent, $failed];
    }

    private function record(string $kind, string $subject, string $body, ?string $own, ?int $seminarId, string $audience, int $sent, int $failed, User $by): Mailing
    {
        $mailing = Mailing::query()->create(['kind' => $kind, 'subject' => $subject, 'body' => $body, 'own_text' => $own, 'seminar_id' => $seminarId, 'audience' => $audience, 'recipients_count' => $sent, 'failed_count' => $failed, 'sent_by_user_id' => $by->id]);

        AuditLog::record(AuditEvent::MailingSent, $by, null, ['art' => $kind === Mailing::KIND_MEMBERS ? 'Rundschreiben an Mitglieder' : 'Mitteilung an Teamende', 'empfaenger' => $sent, 'fehlgeschlagen' => $failed]);

        return $mailing;
    }

    /**
     * @param  array{email: string, first: string, last: string}  $recipient
     * @return array<string, string>
     */
    private function personal(array $recipient, User $by): array
    {
        $first = $recipient['first'] !== '' ? $recipient['first'] : 'Mitglied';
        $name = trim($recipient['first'].' '.$recipient['last']);

        return [
            'vorname' => $first,
            'nachname' => $recipient['last'],
            'name' => $name !== '' ? $name : $first,
            'absender' => $this->senderName($by),
            'verein' => 'Verband für Demokratiebildung und Bibliotheken an Schulen e. V.',
            'portal' => url('/'),
        ];
    }

    /** Die schreibende Person als Beispiel-Empfänger für Vorschau und Testmail. @return array{email: string, first: string, last: string} */
    private function sample(User $by): array
    {
        return ['email' => (string) $by->email, 'first' => (string) ($by->person?->first_name ?? ''), 'last' => (string) ($by->person?->last_name ?? '')];
    }

    private function senderName(User $by): string
    {
        $by->loadMissing('person');

        return $by->person?->fullName() ?: (string) $by->email;
    }
}
