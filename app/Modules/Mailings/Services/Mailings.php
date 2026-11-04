<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Mailings\Mail\MailingApprovalMail;
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

    public function __construct(private readonly MailRecipients $recipients, private readonly MailTemplates $templates, private readonly MailSuppressions $suppressions, private readonly MailUnsubscribes $unsubscribes, private readonly NotificationPreferences $preferences, private readonly MailAttachments $attachmentFiles) {}

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
            'wochentag' => $date?->isoFormat('dddd') ?? '',
            'tage_bis' => $date !== null ? (string) (int) today()->diffInDays($date->copy()->startOfDay(), false) : '',
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
     * Schreiben an eine oder mehrere Gruppen, jede Adresse einmal: legt den Entwurf an und legt ihn sofort vor (Versand, Planung oder Freigabe, je nach Regel).
     * Gibt es keine Empfänger, bleibt kein neuer Entwurf zurück.
     *
     * @param  list<string>  $groups
     * @param  array<string, mixed>  $meeting
     *
     * @throws DomainException
     */
    public function sendGroups(array $groups, string $subject, string $body, array $meeting, User $by, bool $mandatory = false, ?Carbon $sendAt = null, string $area = 'office', ?Mailing $draft = null, array $exclude = [], array $people = []): Mailing
    {
        $mailing = $this->saveDraft(['groups' => $groups, 'subject' => $subject, 'body' => $body, 'meeting' => $meeting, 'mandatory' => $mandatory, 'send_at' => $sendAt, 'exclude' => $exclude, 'people' => $people], $by, $area, $draft);

        try {
            return $this->submit($mailing, $by);
        } catch (DomainException $exception) {
            if ($draft === null) {
                $mailing->delete();
            }

            throw $exception;
        }
    }

    // ---------- Entwurf, Freigabe, Planung ----------

    /**
     * Speichert den Entwurf eines Schreibens an Gruppen (neu oder als Änderung eines eigenen Entwurfs).
     *
     * @param  array{groups: list<string>, subject: string, body: string, meeting: array<string, mixed>, mandatory: bool, send_at: ?Carbon}  $data
     */
    public function saveDraft(array $data, User $by, string $area, ?Mailing $draft = null): Mailing
    {
        $attributes = [
            'kind' => Mailing::KIND_GENERAL,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'groups' => array_values($data['groups']),
            'mandatory' => (bool) $data['mandatory'],
            'send_at' => $data['send_at'] ?? null,
            'payload' => ['meeting' => $data['meeting'], 'exclude' => array_values($data['exclude'] ?? []), 'people' => array_values($data['people'] ?? [])],
            'sender_area' => $area,
            'status' => Mailing::STATUS_DRAFT,
            'sent_by_user_id' => $by->id,
        ];

        if ($draft === null) {
            return Mailing::query()->create($attributes + ['recipients_count' => 0, 'sent_count' => 0, 'failed_count' => 0]);
        }

        $draft->forceFill($attributes)->save();

        return $draft;
    }

    /**
     * Vorschau eines vorbereiteten Schreibens (Entwurf, vorgelegt, geplant) mit den Angaben der ansehenden Person als Beispiel.
     *
     * @return array{subject: string, html: string}
     */
    public function previewMailing(Mailing $mailing, User $viewer): array
    {
        $meeting = (array) (($mailing->payload ?? [])['meeting'] ?? []);
        $author = $mailing->sentBy ?? $viewer;

        return $this->render($mailing->subject, $mailing->body, $this->meetingValues($meeting), $this->sample($viewer), $this->senderName($author));
    }

    /**
     * Fristwarnung für Einladungen zu Versammlungen: liegt zwischen Versand (geplanter Zeitpunkt, sonst heute) und Versammlung weniger als die
     * eingestellte Einladungsfrist? Nur eine Warnung, kein Sperren. Gilt nur, wenn die Gruppe Mitglieder dabei ist und ein Datum angegeben wurde.
     *
     * @param  list<string>  $groups
     * @param  array<string, mixed>  $meeting
     */
    public function noticeWarning(array $groups, array $meeting, ?Carbon $sendAt = null): ?string
    {
        $required = (int) config('portal.mailings.invitation_notice_days', 14);

        if ($required <= 0 || ! in_array('members', $groups, true) || ! filled($meeting['date'] ?? null)) {
            return null;
        }

        $send = ($sendAt !== null && $sendAt->gt(now()) ? $sendAt->copy() : now())->startOfDay();
        $meetingDay = Carbon::parse((string) $meeting['date'])->startOfDay();
        $days = (int) $send->diffInDays($meetingDay, false);

        if ($days >= $required) {
            return null;
        }

        $hint = ' Die eingestellte Einladungsfrist beträgt '.$required.' Tage. Bitte gleichen Sie sie mit der Satzung ab.';

        if ($days < 0) {
            return 'Die Versammlung am '.$meetingDay->format('d.m.Y').' liegt vor dem Versand am '.$send->format('d.m.Y').'.'.$hint;
        }

        return 'Zwischen Versand ('.$send->format('d.m.Y').') und Versammlung ('.$meetingDay->format('d.m.Y').') '.($days === 1 ? 'liegt nur 1 Tag' : 'liegen nur '.$days.' Tage').'.'.$hint;
    }

    /** Gilt für dieses Schreiben die Freigabepflicht? (Konfiguration portal.mailings.approval; wer freigeben darf, schreibt frei.) */
    public function needsApproval(User $by, int $recipients): bool
    {
        $config = (array) config('portal.mailings.approval', []);

        if (! ($config['enabled'] ?? true) || $by->can('mailings.approve')) {
            return false;
        }

        return $recipients >= max(1, (int) ($config['threshold'] ?? 50));
    }

    /**
     * Legt einen Entwurf vor: Freigabe, Planung oder sofortiger Versand.
     *
     * @throws DomainException
     */
    public function submit(Mailing $mailing, User $by): Mailing
    {
        if ($mailing->status !== Mailing::STATUS_DRAFT) {
            throw new DomainException('Nur ein Entwurf lässt sich vorlegen.');
        }

        $count = count($this->recipients->groups((array) $mailing->groups, (bool) $mailing->mandatory, $mailing->excludedGroups(), $mailing->chosenPeople()));

        if ($count === 0) {
            throw new DomainException('Für diese Auswahl gibt es keine Empfänger mit E-Mail-Adresse. Wer Mitteilungen abbestellt hat, bekommt sie nicht.');
        }

        $mailing->forceFill(['recipients_count' => $count])->save();

        if ($this->needsApproval($by, $count)) {
            $mailing->forceFill(['status' => Mailing::STATUS_PENDING, 'rejection_note' => null])->save();
            $this->workflow('zur Freigabe vorgelegt', $by, $count);
            $this->notifyApprovers($mailing, $by);

            return $mailing->fresh();
        }

        return $this->proceed($mailing, $by);
    }

    /**
     * Gibt ein vorgelegtes Schreiben frei. Das eigene Schreiben lässt sich nicht freigeben.
     *
     * @throws DomainException
     */
    public function approve(Mailing $mailing, User $approver): Mailing
    {
        if ($mailing->status !== Mailing::STATUS_PENDING) {
            throw new DomainException('Dieses Schreiben wartet nicht auf eine Freigabe.');
        }

        if ($approver->id === $mailing->sent_by_user_id) {
            throw new DomainException('Ein eigenes Schreiben können Sie nicht freigeben.');
        }

        $mailing->forceFill(['approved_by_user_id' => $approver->id, 'approved_at' => now()])->save();
        $this->workflow('freigegeben', $approver, (int) $mailing->recipients_count);

        return $this->proceed($mailing, $approver);
    }

    /** Gibt ein vorgelegtes Schreiben mit Begründung als Entwurf zurück. */
    public function reject(Mailing $mailing, User $by, string $note): void
    {
        if ($mailing->status !== Mailing::STATUS_PENDING) {
            throw new DomainException('Dieses Schreiben wartet nicht auf eine Freigabe.');
        }

        $mailing->forceFill(['status' => Mailing::STATUS_DRAFT, 'rejection_note' => trim($note), 'approved_by_user_id' => null, 'approved_at' => null])->save();
        $this->workflow('zurückgegeben', $by, (int) $mailing->recipients_count);
    }

    /** Zieht ein vorgelegtes oder geplantes Schreiben zurück in den Entwurf. */
    public function withdraw(Mailing $mailing, User $by): void
    {
        if (! in_array($mailing->status, [Mailing::STATUS_PENDING, Mailing::STATUS_SCHEDULED], true)) {
            throw new DomainException('Dieses Schreiben lässt sich nicht zurückziehen.');
        }

        $mailing->forceFill(['status' => Mailing::STATUS_DRAFT, 'approved_by_user_id' => null, 'approved_at' => null])->save();
        $this->workflow('zurückgezogen', $by, (int) $mailing->recipients_count);
    }

    public function deleteDraft(Mailing $mailing): void
    {
        if ($mailing->status !== Mailing::STATUS_DRAFT) {
            throw new DomainException('Nur ein Entwurf lässt sich löschen.');
        }

        $mailing->attachments()->get()->each->delete();
        $mailing->delete();
    }

    /** Nach der Freigabe oder ohne Freigabepflicht: später planen oder sofort senden. */
    private function proceed(Mailing $mailing, User $by): Mailing
    {
        if ($mailing->send_at !== null && $mailing->send_at->gt(now()->addMinute())) {
            $mailing->forceFill(['status' => Mailing::STATUS_SCHEDULED])->save();
            $this->workflow('geplant', $by, (int) $mailing->recipients_count);

            return $mailing->fresh();
        }

        return $this->launch($mailing);
    }

    /**
     * Startet den Versand eines vorbereiteten Schreibens: Empfänger jetzt bestimmen (mit Abmeldungen und Sperren von jetzt), Zustellzeilen anlegen, Worker beauftragen.
     *
     * @throws DomainException
     */
    public function launch(Mailing $mailing): Mailing
    {
        $stats = $this->recipients->groupsWithStats((array) $mailing->groups, (bool) $mailing->mandatory, $mailing->excludedGroups(), $mailing->chosenPeople());
        $list = $stats['kept'];

        if ($list === []) {
            throw new DomainException('Für diese Auswahl gibt es keine Empfänger mit E-Mail-Adresse. Wer Mitteilungen abbestellt hat, bekommt sie nicht.');
        }

        $author = $mailing->sentBy;
        $meeting = (array) (($mailing->payload ?? [])['meeting'] ?? []);

        $mailing->forceFill([
            'status' => Mailing::STATUS_QUEUED,
            'queued_at' => now(),
            'recipients_count' => count($list),
            'sent_count' => 0,
            'failed_count' => 0,
            'values' => ['common' => $this->meetingValues($meeting), 'absender' => $author !== null ? $this->senderName($author) : 'Verein', 'excluded' => ['unsubscribed' => $stats['unsubscribed'], 'suppressed' => $stats['suppressed'], 'groups' => $stats['excluded']]],
        ])->save();

        $this->insertDeliveries($mailing, $list);

        AuditLog::record(AuditEvent::MailingSent, $author, null, ['art' => 'Schreiben an Gruppen', 'gruppen' => MailGroups::labels((array) $mailing->groups), 'empfaenger' => count($list)]);

        SendMailing::dispatch($mailing->id);

        return $mailing->fresh();
    }

    /** Hinweis an die Vorstände, die Freigaben bekommen wollen (nicht an die Person, die vorlegt). */
    private function notifyApprovers(Mailing $mailing, User $author): void
    {
        $users = User::query()
            ->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', 'board_member')))
            ->get()
            ->filter(fn (User $user): bool => $user->status === UserStatus::Active && $user->id !== $author->id && $user->can('mailings.approve'))
            ->all();

        $url = route('mailings.board.show', $mailing);

        foreach ($this->preferences->filter($users, 'mailing_approvals') as $approver) {
            try {
                Mail::to($approver->email)->send(new MailingApprovalMail($mailing, $this->senderName($author), $url));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /** Protokoll ohne Text und Adressen: nur Aktion und Zahl. */
    private function workflow(string $action, ?User $by, int $recipients): void
    {
        AuditLog::record(AuditEvent::MailingWorkflow, $by, null, ['aktion' => $action, 'empfaenger' => $recipients]);
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
            'seminar_nr' => (string) ($seminar->number ?? ''),
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
    private function render(string $subject, string $body, array $values, array $recipient, string $senderName, bool $mandatory = false): array
    {
        $all = $this->personal($recipient, $senderName, $mandatory) + $values;

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
            'status' => Mailing::STATUS_QUEUED, 'queued_at' => now(), 'mandatory' => $mandatory, 'sender_area' => $kind === Mailing::KIND_TEAM ? 'team' : 'office',
            'values' => ['common' => $values, 'absender' => $this->senderName($by)],
        ]);

        $this->insertDeliveries($mailing, $list);

        AuditLog::record(AuditEvent::MailingSent, $by, null, ['art' => $kind === Mailing::KIND_GENERAL ? 'Schreiben an Gruppen' : 'Mitteilung an Teamende', 'gruppen' => $groups !== null ? MailGroups::labels($groups) : null, 'empfaenger' => count($list)]);

        SendMailing::dispatch($mailing->id);

        return $mailing->fresh();
    }

    /** @param  list<array<string, string>>  $list */
    private function insertDeliveries(Mailing $mailing, array $list): void
    {
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
    }

    /**
     * Text und Betreff für eine Zustellung: Platzhalter des Schreibens mit den Angaben der Person.
     *
     * @return array{subject: string, html: string}
     */
    public function renderDelivery(Mailing $mailing, MailingDelivery $delivery): array
    {
        $recipient = ['email' => $delivery->email, 'first' => $delivery->first_name, 'last' => $delivery->last_name, 'fallback' => $delivery->fallback, 'group' => $delivery->group];

        return $this->render($mailing->subject, $mailing->body, (array) ($mailing->values['common'] ?? []), $recipient, (string) ($mailing->values['absender'] ?? 'Verein'), (bool) $mailing->mandatory);
    }

    /** Sendet die Mail einer Zustellung. Wirft bei einem Fehler des Mailversands. */
    public function sendDelivery(Mailing $mailing, MailingDelivery $delivery): void
    {
        $rendered = $this->renderDelivery($mailing, $delivery);

        $topic = MailGroups::topicFor($delivery->group);
        $unsubscribeUrl = ! $mailing->mandatory && $topic !== null ? $this->unsubscribes->url($delivery->email, $topic) : null;

        $sender = (array) config('portal.mailings.senders.'.($mailing->sender_area ?: 'office'), []);

        Mail::to($delivery->email)->send(new MailingMail($rendered['subject'], $rendered['html'], $delivery->group ?: 'internal', $unsubscribeUrl, (bool) $mailing->mandatory, $sender['name'] ?? null, $sender['reply_to'] ?? null, $this->attachmentFiles->files($mailing->loadMissing('attachments'))));
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
        if (! $mailing->isActive() && $mailing->status !== Mailing::STATUS_SCHEDULED) {
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
        if ($mailing->status === Mailing::STATUS_DRAFT) {
            return 'Der Entwurf wurde gespeichert.';
        }

        if ($mailing->status === Mailing::STATUS_PENDING) {
            return 'Das Schreiben liegt dem Vorstand zur Freigabe vor ('.$mailing->recipients_count.' Empfänger). Sie bekommen den Stand hier angezeigt.';
        }

        if ($mailing->status === Mailing::STATUS_SCHEDULED) {
            return 'Das Schreiben ist für den '.$mailing->send_at?->format('d.m.Y, H:i').' Uhr geplant.';
        }

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
    private function personal(array $recipient, string $senderName, bool $mandatory = false): array
    {
        $topic = MailGroups::topicFor($recipient['group'] ?? null);
        $first = $recipient['first'] !== '' ? $recipient['first'] : ($recipient['fallback'] ?? 'Mitglied');
        $name = trim($recipient['first'].' '.$recipient['last']);

        return [
            'vorname' => $first,
            'nachname' => $recipient['last'],
            'name' => $name !== '' ? $name : $first,
            'absender' => $senderName,
            'verein' => 'Verband für Demokratiebildung und Bibliotheken an Schulen e. V.',
            'portal' => url('/'),
            'email' => (string) ($recipient['email'] ?? ''),
            'heute' => now()->format('d.m.Y'),
            'jahr' => (string) now()->year,
            'abmelden' => ! $mandatory && $topic !== null && filled($recipient['email'] ?? null) ? $this->unsubscribes->url((string) $recipient['email'], $topic) : '',
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
