<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Mailings\Mail\MailingMail;
use App\Modules\Mailings\Jobs\SendMailing;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingDelivery;
use App\Modules\Mailings\Support\MailGroups;
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

    public function __construct(private readonly MailRecipients $recipients, private readonly MailTemplates $templates, private readonly MailSuppressions $suppressions, private readonly MailUnsubscribes $unsubscribes) {}

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
    public function previewGroups(string $subject, string $body, array $meeting, User $by): array
    {
        return $this->render($subject, $body, $this->meetingValues($meeting), $this->sample($by), $this->senderName($by));
    }

    /**
     * Schreiben an eine oder mehrere Gruppen, jede Adresse einmal.
     *
     * @param  list<string>  $groups
     * @param  array<string, mixed>  $meeting
     *
     * @throws DomainException
     */
    public function sendGroups(array $groups, string $subject, string $body, array $meeting, User $by, bool $mandatory = false): Mailing
    {
        $list = $this->recipients->groups($groups, $mandatory);

        if ($list === []) {
            throw new DomainException('Für diese Auswahl gibt es keine Empfänger mit E-Mail-Adresse. Wer Mitteilungen abbestellt hat, bekommt sie nicht.');
        }

        return $this->queue(Mailing::KIND_GENERAL, $subject, $body, null, null, null, $groups, $list, $this->meetingValues($meeting), $by, $mandatory);
    }

    /** @param  array<string, mixed>  $meeting */
    public function sendGroupsTest(string $subject, string $body, array $meeting, User $by): void
    {
        $this->deliver([$this->sample($by)], '[Test] '.$subject, $body, $this->meetingValues($meeting), $by);
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

        return $this->render($composed['subject'], $composed['body'], $this->seminarValues($seminar), $this->sample($by), $this->senderName($by));
    }

    /** @throws DomainException */
    public function sendTeam(Seminar $seminar, string $audience, string $ownText, User $by): Mailing
    {
        $list = $this->recipients->team($seminar, $audience);

        if ($list === []) {
            throw new DomainException('Für diese Auswahl gibt es keine Empfänger. Teamende, die diese Mitteilungen abbestellt haben, bekommen sie nicht.');
        }

        $composed = $this->composeTeam($ownText);

        return $this->queue(Mailing::KIND_TEAM, $composed['subject'], $composed['body'], RichText::clean($ownText), $seminar->id, $audience, null, $list, $this->seminarValues($seminar), $by);
    }

    public function sendTeamTest(Seminar $seminar, string $ownText, User $by): void
    {
        $composed = $this->composeTeam($ownText);

        $this->deliver([$this->sample($by)], '[Test] '.$composed['subject'], $composed['body'], $this->seminarValues($seminar), $by);
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
     * @param  array<string, string>  $recipient
     * @return array{subject: string, html: string}
     */
    private function render(string $subject, string $body, array $values, array $recipient, string $senderName): array
    {
        $all = $this->personal($recipient, $senderName) + $values;

        return ['subject' => MailPlaceholders::render($subject, $all), 'html' => MailPlaceholders::render($body, $all, true, self::RAW)];
    }

    /**
     * @param  list<array<string, string>>  $list
     * @param  array<string, string>  $values
     * @return array{0: int, 1: int} gesendet, fehlgeschlagen
     */
    private function deliver(array $list, string $subject, string $body, array $values, User $by): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($list as $recipient) {
            $rendered = $this->render($subject, $body, $values, $recipient, $this->senderName($by));

            try {
                Mail::to($recipient['email'])->send(new MailingMail($rendered['subject'], $rendered['html'], $recipient['group'] ?? 'internal'));
                $sent++;
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
            }
        }

        return [$sent, $failed];
    }

    /**
     * Legt das Schreiben und eine Zustellzeile je Adresse an und übergibt den Versand an den Worker.
     * Mit dem Treiber „sync“ (Tests, einfache Einrichtungen) läuft der Versand sofort mit.
     *
     * @param  list<string>|null  $groups
     * @param  list<array<string, string>>  $list
     * @param  array<string, string>  $values  Werte der Platzhalter, die für alle gleich sind
     */
    private function queue(string $kind, string $subject, string $body, ?string $own, ?int $seminarId, ?string $audience, ?array $groups, array $list, array $values, User $by, bool $mandatory = false): Mailing
    {
        $mailing = Mailing::query()->create([
            'kind' => $kind, 'subject' => $subject, 'body' => $body, 'own_text' => $own, 'seminar_id' => $seminarId, 'audience' => $audience, 'groups' => $groups,
            'recipients_count' => count($list), 'sent_count' => 0, 'failed_count' => 0, 'sent_by_user_id' => $by->id,
            'status' => Mailing::STATUS_QUEUED, 'queued_at' => now(), 'mandatory' => $mandatory,
            'values' => ['common' => $values, 'absender' => $this->senderName($by)],
        ]);

        $now = now();

        foreach (array_chunk($list, 500) as $chunk) {
            MailingDelivery::query()->insert(array_map(fn (array $recipient): array => [
                'mailing_id' => $mailing->id,
                'email' => $recipient['email'],
                'first_name' => $recipient['first'] ?? '',
                'last_name' => $recipient['last'] ?? '',
                'fallback' => $recipient['fallback'] ?? 'Mitglied',
                'group' => $recipient['group'] ?? 'internal',
                'status' => MailingDelivery::PENDING,
                'attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        AuditLog::record(AuditEvent::MailingSent, $by, null, ['art' => $kind === Mailing::KIND_GENERAL ? 'Schreiben an Gruppen' : 'Mitteilung an Teamende', 'gruppen' => $groups !== null ? MailGroups::labels($groups) : null, 'empfaenger' => count($list)]);

        SendMailing::dispatch($mailing->id);

        return $mailing->fresh();
    }

    /**
     * Text und Betreff für eine Zustellung: Platzhalter des Schreibens mit den Angaben der Person.
     *
     * @return array{subject: string, html: string}
     */
    public function renderDelivery(Mailing $mailing, MailingDelivery $delivery): array
    {
        $recipient = ['email' => $delivery->email, 'first' => $delivery->first_name, 'last' => $delivery->last_name, 'fallback' => $delivery->fallback, 'group' => $delivery->group];

        return $this->render($mailing->subject, $mailing->body, (array) ($mailing->values['common'] ?? []), $recipient, (string) ($mailing->values['absender'] ?? 'Verein'));
    }

    /** Sendet die Mail einer Zustellung. Wirft bei einem Fehler des Mailversands. */
    public function sendDelivery(Mailing $mailing, MailingDelivery $delivery): void
    {
        $rendered = $this->renderDelivery($mailing, $delivery);

        $topic = MailGroups::topicFor($delivery->group);
        $unsubscribeUrl = ! $mailing->mandatory && $topic !== null ? $this->unsubscribes->url($delivery->email, $topic) : null;

        Mail::to($delivery->email)->send(new MailingMail($rendered['subject'], $rendered['html'], $delivery->group ?: 'internal', $unsubscribeUrl, (bool) $mailing->mandatory));
    }

    /**
     * Grund, warum an diese Adresse jetzt nicht gesendet wird (gesperrt oder abgemeldet), sonst null.
     * Wird beim Senden geprüft, nicht nur beim Anlegen: Eine Abmeldung dazwischen wirkt noch.
     */
    public function deliveryBlock(Mailing $mailing, MailingDelivery $delivery): ?string
    {
        if ($this->suppressions->isSuppressed($delivery->email)) {
            return 'Adresse gesperrt';
        }

        $topic = MailGroups::topicFor($delivery->group);

        if (! $mailing->mandatory && $topic !== null && $this->unsubscribes->isUnsubscribed($delivery->email, $topic)) {
            return 'Abgemeldet';
        }

        return null;
    }

    /** Bricht ein laufendes Schreiben ab: offene Zustellungen werden übersprungen, Gesendetes bleibt. */
    public function cancel(Mailing $mailing): void
    {
        if (! $mailing->isActive()) {
            return;
        }

        $mailing->forceFill(['status' => Mailing::STATUS_CANCELLED, 'cancelled_at' => now(), 'finished_at' => now()])->save();
        $mailing->deliveries()->whereIn('status', [MailingDelivery::PENDING, MailingDelivery::SENDING])->update(['status' => MailingDelivery::SKIPPED]);
        $this->refreshCounts($mailing);
    }

    /**
     * Fehlgeschlagene Zustellungen noch einmal versuchen.
     *
     * @throws DomainException
     */
    public function retryFailed(Mailing $mailing): int
    {
        if ($mailing->isActive()) {
            throw new DomainException('Das Schreiben wird noch gesendet.');
        }

        $count = $mailing->deliveries()->where('status', MailingDelivery::FAILED)->update(['status' => MailingDelivery::PENDING, 'attempts' => 0, 'error' => null]);

        if ($count === 0) {
            throw new DomainException('Es gibt keine fehlgeschlagenen Zustellungen.');
        }

        $mailing->forceFill(['status' => Mailing::STATUS_QUEUED, 'finished_at' => null, 'cancelled_at' => null])->save();
        $this->refreshCounts($mailing);

        SendMailing::dispatch($mailing->id);

        return $count;
    }

    /** Zähler des Schreibens aus den Zustellzeilen neu berechnen. */
    public function refreshCounts(Mailing $mailing): void
    {
        $counts = $mailing->deliveries()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $mailing->forceFill(['sent_count' => (int) ($counts[MailingDelivery::SENT] ?? 0), 'failed_count' => (int) ($counts[MailingDelivery::FAILED] ?? 0)])->save();
    }

    /** Hinweis nach dem Anlegen, je nachdem ob der Versand schon durch ist. */
    public function flashMessage(Mailing $mailing): string
    {
        if ($mailing->isActive()) {
            return 'Das Schreiben ist in der Warteschlange ('.$mailing->recipients_count.' Empfänger). Den Fortschritt sehen Sie hier.';
        }

        $note = $mailing->failed_count > 0 ? ' '.$mailing->failed_count.' Zustellungen sind fehlgeschlagen.' : '';

        return 'Das Schreiben wurde an '.$mailing->sent_count.' Personen gesendet.'.$note;
    }

    /**
     * @param  array<string, string>  $recipient
     * @return array<string, string>
     */
    private function personal(array $recipient, string $senderName): array
    {
        $first = $recipient['first'] !== '' ? $recipient['first'] : ($recipient['fallback'] ?? 'Mitglied');
        $name = trim($recipient['first'].' '.$recipient['last']);

        return [
            'vorname' => $first,
            'nachname' => $recipient['last'],
            'name' => $name !== '' ? $name : $first,
            'absender' => $senderName,
            'verein' => 'Verband für Demokratiebildung und Bibliotheken an Schulen e. V.',
            'portal' => url('/'),
        ];
    }

    /** Die schreibende Person als Beispiel-Empfänger für Vorschau und Testmail. @return array<string, string> */
    private function sample(User $by): array
    {
        return ['email' => (string) $by->email, 'first' => (string) ($by->person?->first_name ?? ''), 'last' => (string) ($by->person?->last_name ?? ''), 'fallback' => 'Mitglied', 'group' => 'internal'];
    }

    private function senderName(User $by): string
    {
        $by->loadMissing('person');

        return $by->person?->fullName() ?: (string) $by->email;
    }
}
