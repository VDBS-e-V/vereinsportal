<?php

namespace App\Modules\Contacts\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Contacts\Models\ImportBatch;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\Mailings\Services\MailUnsubscribes;
use Illuminate\Support\Facades\DB;

/**
 * Import der Mailingliste. Es genügen die E-Mail-Adressen; Vorname, Nachname und Telefon werden übernommen, wenn die Liste sie enthält.
 * Zuerst gibt es eine Vorschau (nichts wird gespeichert), danach den Import: alles oder nichts, fehlerhafte Zeilen werden mit Zeilennummer genannt.
 * Vorhandene Daten werden nie überschrieben, nur leere Felder ergänzt. Gesperrte und abgemeldete Adressen werden übernommen, aber nicht angeschrieben.
 */
final class ContactImporter
{
    public const MAX_ROWS = 5000;

    private const EMAIL_HEADERS = ['e-mail', 'email', 'e-mail-adresse', 'e-mailadresse', 'mail', 'adresse'];

    private const FIELD_HEADERS = ['vorname' => 'first_name', 'nachname' => 'last_name', 'telefon' => 'phone'];

    public function __construct(private readonly MailSuppressions $suppressions, private readonly MailUnsubscribes $unsubscribes) {}

    /** @return list<list<string>> Vorlage für den Download */
    public static function template(): array
    {
        return [['E-Mail', 'Vorname', 'Nachname', 'Telefon'], ['beispiel@example.org', 'Beispiel', 'Person', '']];
    }

    /**
     * Prüft die Liste, ohne etwas zu speichern.
     *
     * @return array{rows: list<array<string, mixed>>, counts: array<string, int>, errors: list<string>}
     */
    public function preview(string $contents): array
    {
        $parsed = $this->parse($contents);

        if (isset($parsed['error'])) {
            return ['rows' => [], 'counts' => $this->emptyCounts(), 'errors' => [$parsed['error']]];
        }

        $rows = [];
        $errors = [];
        $seen = [];
        $counts = $this->emptyCounts();

        $emails = array_values(array_filter(array_map(fn (array $raw): string => mb_strtolower($raw['email']), $parsed['rows'])));
        $existing = TeamContact::query()->whereIn('email', $emails)->get()->keyBy('email');
        $suppressed = $this->suppressions->among($emails);
        $unsubscribed = $this->unsubscribes->among($emails)['team_mailings'] ?? [];

        foreach ($parsed['rows'] as $line => $raw) {
            $email = mb_strtolower($raw['email']);
            $row = $raw + ['line' => $line, 'email' => $email];

            if ($email === '' || mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $row['status'] = 'error';
                $row['message'] = 'Ungültige E-Mail-Adresse';
                $errors[] = "Zeile {$line}: Ungültige E-Mail-Adresse „{$raw['email']}“.";
                $counts['errors']++;
            } elseif (isset($seen[$email])) {
                $row['status'] = 'duplicate';
                $row['message'] = "Kommt schon in Zeile {$seen[$email]} vor (wird übersprungen)";
                $counts['duplicates']++;
            } else {
                $seen[$email] = $line;

                if (isset($existing[$email])) {
                    $row['status'] = 'existing';
                    $row['message'] = 'Gibt es schon (leere Felder werden ergänzt)';
                    $counts['existing']++;
                } else {
                    $row['status'] = 'new';
                    $row['message'] = 'Neu';
                    $counts['new']++;
                }

                if (isset($suppressed[$email]) || isset($unsubscribed[$email])) {
                    $row['blocked'] = true;
                    $row['message'] .= ', wird nicht angeschrieben (gesperrt oder abgemeldet)';
                    $counts['blocked']++;
                }
            }

            $rows[] = $row;
        }

        $counts['total'] = count($rows);

        return ['rows' => $rows, 'counts' => $counts, 'errors' => $errors];
    }

    /**
     * @return array{batch: ImportBatch|null, created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function import(string $contents, User $by, ?string $fileName = null): array
    {
        $preview = $this->preview($contents);

        if ($preview['errors'] !== []) {
            return ['batch' => null, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => $preview['errors']];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $batch = DB::transaction(function () use ($preview, $by, $fileName, &$created, &$updated, &$skipped): ImportBatch {
            $batch = ImportBatch::query()->create(['kind' => 'mailing_list', 'file_name' => $fileName !== null ? mb_substr($fileName, 0, 190) : null, 'created_by_user_id' => $by->id, 'consent_confirmed_at' => now()]);

            foreach ($preview['rows'] as $row) {
                if ($row['status'] === 'duplicate') {
                    $skipped++;

                    continue;
                }

                $data = array_filter(['first_name' => $row['first_name'] ?? null, 'last_name' => $row['last_name'] ?? null, 'phone' => $row['phone'] ?? null], fn ($value): bool => filled($value));
                $contact = TeamContact::query()->where('email', $row['email'])->first();

                if ($contact === null) {
                    TeamContact::query()->create($data + ['email' => $row['email'], 'import_batch_id' => $batch->id, 'consent_confirmed_at' => now()]);
                    $created++;

                    continue;
                }

                $fill = array_filter($data, fn ($value, $key): bool => blank($contact->{$key}), ARRAY_FILTER_USE_BOTH);

                if ($fill !== []) {
                    $contact->forceFill($fill)->save();
                    $updated++;
                } else {
                    $skipped++;
                }
            }

            $batch->forceFill(['rows_total' => count($preview['rows']), 'rows_new' => $created, 'rows_updated' => $updated, 'rows_skipped' => $skipped])->save();

            return $batch;
        });

        AuditLog::record(AuditEvent::ContactsImported, $by, null, ['neu' => $created, 'ergaenzt' => $updated, 'uebersprungen' => $skipped]);

        return ['batch' => $batch, 'created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'errors' => []];
    }

    /** @return array<string, int> */
    private function emptyCounts(): array
    {
        return ['total' => 0, 'new' => 0, 'existing' => 0, 'duplicates' => 0, 'errors' => 0, 'blocked' => 0];
    }

    /**
     * Liest CSV mit Kopfzeile oder eine einfache Liste (eine Adresse je Zeile; dann gelten die Spalten E-Mail, Vorname, Nachname, Telefon in dieser Reihenfolge).
     *
     * @return array{rows: array<int, array{email: string, first_name: string, last_name: string, phone: string}>}|array{error: string}
     */
    private function parse(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $contents = trim($contents);

        if ($contents === '') {
            return ['error' => 'Die Liste ist leer.'];
        }

        $firstLine = strtok($contents, "\n") ?: '';
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        $map = ['email' => 0, 'first_name' => 1, 'last_name' => 2, 'phone' => 3];
        $line = 0;
        $rows = [];
        $first = true;

        while (($record = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $line++;

            if ($record === [null] || count(array_filter($record, fn ($cell): bool => trim((string) $cell) !== '')) === 0) {
                continue;
            }

            if ($first) {
                $first = false;
                $titles = array_map(fn ($title): string => mb_strtolower(trim((string) $title)), $record);

                if (array_intersect($titles, self::EMAIL_HEADERS) !== [] && ! str_contains(implode('', $titles), '@')) {
                    $map = [];

                    foreach ($titles as $index => $title) {
                        if (in_array($title, self::EMAIL_HEADERS, true)) {
                            $map['email'] = $index;
                        } elseif (isset(self::FIELD_HEADERS[$title])) {
                            $map[self::FIELD_HEADERS[$title]] = $index;
                        } elseif ($title !== '') {
                            fclose($handle);

                            return ['error' => 'Unbekannte Spalte „'.trim((string) $record[$index]).'“. Erlaubt sind E-Mail, Vorname, Nachname und Telefon. Laden Sie die Vorlage herunter.'];
                        }
                    }

                    continue;
                }
            }

            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);

                return ['error' => 'Die Liste enthält mehr als '.self::MAX_ROWS.' Adressen. Bitte teilen Sie sie auf.'];
            }

            $cell = fn (string $field): string => trim((string) ($record[$map[$field] ?? -1] ?? ''));
            $rows[$line] = ['email' => $cell('email'), 'first_name' => mb_substr($cell('first_name'), 0, 100), 'last_name' => mb_substr($cell('last_name'), 0, 100), 'phone' => mb_substr($cell('phone'), 0, 50)];
        }

        fclose($handle);

        return $rows === [] ? ['error' => 'Die Liste enthält keine Adressen.'] : ['rows' => $rows];
    }
}
