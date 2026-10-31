<?php

namespace App\Modules\Events\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Events\Mail\EventConfirmMail;
use App\Modules\Events\Mail\EventRegisteredMail;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventAttendance;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Models\EventSession;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * Anmeldungen zu Großveranstaltungen: Selbstanmeldung mit Bestätigungslink (Double-Opt-in) und automatischer Anmeldebestätigung mit Ticketcode,
 * Listen-Upload durch die Koordination (alles oder nichts), Anwesenheit je Termin und die Daten für die Teilnahmebestätigung.
 * Die Antwort an Besucher ist bei Dubletten immer gleich. Namen und Geburtsdaten stehen nie im allgemeinen Protokoll.
 */
final class EventRegistrations
{
    public const MAX_ROWS = 500;

    private const TICKET_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** Spaltenüberschrift (klein) => Feld */
    private const HEADERS = [
        'name' => 'name', 'vor- und nachname' => 'name', 'name der schülerin / des schülers' => 'name',
        'schule' => 'school',
        'klasse' => 'class_name', 'klassenstufe' => 'class_name',
        'geburtsdatum' => 'birth_date',
        'e-mail' => 'email', 'email' => 'email', 'e-mail-adresse' => 'email',
    ];

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'school' => ['required', 'string', 'max:200'],
            'class_name' => ['required', 'string', 'max:40'],
            'birth_date' => ['required', 'date', 'before:today', 'after:1950-01-01'],
            'email' => ['required', 'email:rfc', 'max:254'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'name.required' => 'Bitte gib den Namen an.',
            'school.required' => 'Bitte gib die Schule an.',
            'class_name.required' => 'Bitte gib die Klasse an.',
            'birth_date.required' => 'Bitte gib das Geburtsdatum an.',
            'birth_date.date' => 'Bitte gib ein gültiges Geburtsdatum an.',
            'birth_date.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'birth_date.after' => 'Bitte gib ein gültiges Geburtsdatum an.',
            'email.required' => 'Bitte gib eine E-Mail-Adresse an.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse an.',
        ];
    }

    /**
     * Selbstanmeldung. Legt eine unbestätigte Anmeldung an und sendet den Bestätigungslink; bei einer bekannten Anmeldung (gleiche Adresse und Name)
     * geht stattdessen der Link bzw. die Bestätigung erneut raus. Nach außen immer dasselbe Ergebnis.
     *
     * @param  array<string, mixed>  $data  validierte Felder
     *
     * @throws DomainException wenn keine Anmeldung mehr möglich ist
     */
    public function register(Event $event, array $data): void
    {
        $email = mb_strtolower(trim((string) $data['email']));
        $existing = $event->registrations()->where('email', $email)->whereRaw('lower(name) = ?', [mb_strtolower(trim((string) $data['name']))])->first();

        if ($existing instanceof EventRegistration) {
            $existing->isConfirmed() ? $this->sendRegistered($existing) : $this->sendConfirmLink($existing);

            return;
        }

        if (! $event->acceptsRegistrations()) {
            throw new DomainException($event->isFull() ? 'Die Veranstaltung ist leider ausgebucht.' : 'Für diese Veranstaltung ist keine Anmeldung mehr möglich.');
        }

        $registration = $event->registrations()->create([
            'name' => trim((string) $data['name']),
            'school' => trim((string) $data['school']),
            'class_name' => trim((string) $data['class_name']),
            'birth_date' => Carbon::parse($data['birth_date']),
            'email' => $email,
            'ticket_code' => $this->newTicketCode(),
            'source' => EventRegistration::SOURCE_SELF,
        ]);

        $this->sendConfirmLink($registration);
    }

    /** Bestätigt die Anmeldung über den signierten Link. Liefert false, wenn sie verfallen ist oder der Platz inzwischen fehlt. */
    public function confirm(EventRegistration $registration): bool
    {
        if ($registration->isConfirmed()) {
            return true;
        }

        $event = $registration->event;

        if ($registration->created_at->lt(now()->subHours(Event::PENDING_HOURS)) || ($event->capacity !== null && $event->confirmedCount() >= $event->capacity)) {
            return false;
        }

        $registration->forceFill(['verified_at' => now()])->save();
        $this->sendRegistered($registration);

        return true;
    }

    /**
     * Import einer Liste im Auftrag von Lehrkräften. Alles oder nichts.
     *
     * @return array{created: int, errors: list<string>}
     */
    public function import(Event $event, string $contents, User $by, bool $sendMails): array
    {
        $parsed = $this->parse($contents);

        if (isset($parsed['error'])) {
            return ['created' => 0, 'errors' => [$parsed['error']]];
        }

        $errors = [];
        $valid = [];
        $seen = [];

        foreach ($parsed['rows'] as $line => $raw) {
            $row = $this->normalize($raw);
            $validator = Validator::make($row, self::rules(), self::messages());

            if ($validator->fails()) {
                $errors[] = "Zeile {$line}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            $key = mb_strtolower(trim($row['email'])).'|'.mb_strtolower(trim($row['name']));

            if (isset($seen[$key])) {
                $errors[] = "Zeile {$line}: Die Anmeldung kommt in der Datei schon in Zeile {$seen[$key]} vor.";

                continue;
            }

            $seen[$key] = $line;

            if ($event->registrations()->where('email', mb_strtolower(trim($row['email'])))->whereRaw('lower(name) = ?', [mb_strtolower(trim($row['name']))])->exists()) {
                $errors[] = "Zeile {$line}: {$row['name']} ist schon angemeldet.";

                continue;
            }

            $valid[] = $row;
        }

        if ($valid === [] && $errors === []) {
            return ['created' => 0, 'errors' => ['Die Datei enthält keine Anmeldungen.']];
        }

        if ($event->capacity !== null && $errors === [] && $event->takenSpots() + count($valid) > $event->capacity) {
            $errors[] = 'Die Liste hat '.count($valid).' Anmeldungen, aber es sind nur noch '.$event->spotsLeft().' Plätze frei.';
        }

        if ($errors !== []) {
            return ['created' => 0, 'errors' => $errors];
        }

        $created = DB::transaction(function () use ($event, $valid, $by): array {
            return array_map(fn (array $row): EventRegistration => $event->registrations()->create([
                'name' => trim($row['name']),
                'school' => trim($row['school']),
                'class_name' => trim($row['class_name']),
                'birth_date' => Carbon::parse($row['birth_date']),
                'email' => mb_strtolower(trim($row['email'])),
                'ticket_code' => $this->newTicketCode(),
                'source' => EventRegistration::SOURCE_UPLOAD,
                'verified_at' => now(),
                'uploaded_by_user_id' => $by->id,
            ]), $valid);
        });

        AuditLog::record(AuditEvent::EventRegistrationsImported, $by, null, ['veranstaltung' => $event->title, 'anzahl' => count($created)]);

        if ($sendMails) {
            foreach ($created as $registration) {
                $this->sendRegistered($registration);
            }
        }

        return ['created' => count($created), 'errors' => []];
    }

    public function remove(EventRegistration $registration, User $by): void
    {
        AuditLog::record(AuditEvent::EventRegistrationRemoved, $by, null, ['veranstaltung' => $registration->event->title]);

        $registration->delete();
    }

    /** Setzt oder entfernt die Anwesenheit. */
    public function mark(EventSession $session, EventRegistration $registration, bool $present, User $by): void
    {
        if ($present) {
            EventAttendance::query()->firstOrCreate(['registration_id' => $registration->id, 'session_id' => $session->id], ['marked_by_user_id' => $by->id]);
        } else {
            EventAttendance::query()->where('registration_id', $registration->id)->where('session_id', $session->id)->delete();
        }
    }

    /** Anwesenheit per Ticketcode eintragen. */
    public function markByTicket(Event $event, EventSession $session, string $code, User $by): ?EventRegistration
    {
        $registration = $event->registrations()->whereNotNull('verified_at')->where('ticket_code', $this->normalizeTicket($code))->first();

        if ($registration instanceof EventRegistration) {
            $this->mark($session, $registration, true, $by);
        }

        return $registration;
    }

    /** Die Anmeldung für die Teilnahmebestätigung: Ticketcode und E-Mail müssen passen, und es muss mindestens eine Anwesenheit eingetragen sein. */
    public function forCertificate(string $code, string $email): ?EventRegistration
    {
        $registration = EventRegistration::query()
            ->whereNotNull('verified_at')->where('ticket_code', $this->normalizeTicket($code))->where('email', mb_strtolower(trim($email)))
            ->with(['event', 'attendances.session'])->first();

        return $registration instanceof EventRegistration && $registration->attendances->isNotEmpty() ? $registration : null;
    }

    /** Aufbewahrung: unbestätigte Anmeldungen nach Ablauf der Frist und Anmeldungen alter Veranstaltungen nach der Aufbewahrungszeit löschen. @return array{pending: int, old: int} */
    public function prune(bool $dry = false): array
    {
        $pending = EventRegistration::query()->whereNull('verified_at')->where('created_at', '<', now()->subHours(Event::PENDING_HOURS));
        $years = max(1, (int) config('portal.events.retention_years', 3));
        $limit = now()->subYears($years);

        $oldIds = Event::query()->get()->filter(function (Event $event) use ($limit): bool {
            $end = $event->sessions()->max('ends_at');

            return Carbon::parse($end ?? $event->created_at)->lt($limit);
        })->pluck('id');

        $old = EventRegistration::query()->whereIn('event_id', $oldIds);
        $counts = ['pending' => (clone $pending)->count(), 'old' => (clone $old)->count()];

        if (! $dry) {
            $pending->delete();
            $old->delete();
        }

        return $counts;
    }

    public function certificateUrl(): string
    {
        return route('events.public.certificate');
    }

    public function normalizeTicket(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    private function newTicketCode(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < 8; $i++) {
                $code .= self::TICKET_ALPHABET[random_int(0, strlen(self::TICKET_ALPHABET) - 1)];
            }
        } while (EventRegistration::query()->where('ticket_code', $code)->exists());

        return $code;
    }

    private function sendConfirmLink(EventRegistration $registration): void
    {
        $url = URL::temporarySignedRoute('events.public.confirm', now()->addHours(Event::PENDING_HOURS), ['registration' => $registration->id]);

        $this->mail($registration->email, new EventConfirmMail($registration->event, $registration->name, $url));
    }

    private function sendRegistered(EventRegistration $registration): void
    {
        $this->mail($registration->email, new EventRegisteredMail($registration->event->loadMissing('sessions'), $registration, $this->certificateUrl()));
    }

    private function mail(string $to, object $mailable): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @return array{rows?: array<int, array<string, ?string>>, error?: string} */
    private function parse(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($contents, "\n") ?: '';
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter, '"', '\\');

        if (! is_array($header)) {
            fclose($handle);

            return ['error' => 'Die Datei ist leer.'];
        }

        $columns = [];

        foreach ($header as $index => $title) {
            $field = self::HEADERS[mb_strtolower(trim((string) $title))] ?? null;

            if ($field !== null && ! in_array($field, $columns, true)) {
                $columns[$index] = $field;
            }
        }

        $missing = array_diff(['name', 'school', 'class_name', 'birth_date', 'email'], $columns);

        if ($missing !== []) {
            fclose($handle);

            return ['error' => 'In der Kopfzeile fehlen Spalten. Erwartet werden: Name, Schule, Klasse, Geburtsdatum, E-Mail.'];
        }

        $rows = [];
        $line = 1;

        while (($record = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $line++;

            if ($record === [null] || count(array_filter($record, fn ($cell) => trim((string) $cell) !== '')) === 0) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);

                return ['error' => 'Die Datei hat mehr als '.self::MAX_ROWS.' Zeilen.'];
            }

            $row = [];

            foreach ($columns as $index => $field) {
                $row[$field] = isset($record[$index]) ? trim((string) $record[$index]) : null;
            }

            $rows[$line] = $row;
        }

        fclose($handle);

        return ['rows' => $rows];
    }

    /** @param  array<string, ?string>  $row @return array<string, ?string> */
    private function normalize(array $row): array
    {
        $date = (string) ($row['birth_date'] ?? '');

        foreach (['d.m.Y', 'Y-m-d', 'd.m.y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $date);
            } catch (Throwable) {
                continue;
            }

            if ($parsed !== false && $parsed->format($format) === $date) {
                $row['birth_date'] = $parsed->toDateString();

                break;
            }
        }

        return $row;
    }

    /** Vorlage für den Download. @return list<list<string>> */
    public static function template(): array
    {
        return [['Name', 'Schule', 'Klasse', 'Geburtsdatum', 'E-Mail'], ['Max Mustermann', 'Beispielschule', '9a', '14.03.2010', 'max@beispiel.example']];
    }
}
