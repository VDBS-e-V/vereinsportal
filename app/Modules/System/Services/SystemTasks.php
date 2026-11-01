<?php

namespace App\Modules\System\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\System\Models\SystemTaskRun;
use App\Modules\System\Support\Redactor;
use DomainException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Feste Positivliste der Aufgaben, die die Administration im Browser auslösen darf. Es gibt kein freies Befehlsfeld:
 * jede Aufgabe ist ein bekannter Artisan-Befehl mit festen Optionen. Aufgaben mit Risiko „high“ verlangen das Passwort.
 * Nie dabei: migrate:fresh, db:wipe und alles, was Daten ohne Fristen löscht.
 */
final class SystemTasks
{
    public const RISK_LOW = 'low';

    public const RISK_MEDIUM = 'medium';

    public const RISK_HIGH = 'high';

    /** @return array<string, array{group: string, label: string, description: string, command: string, params: array<string, mixed>, risk: string}> */
    public function all(): array
    {
        return [
            'cache-leeren' => ['group' => 'Zwischenspeicher', 'label' => 'Cache leeren', 'description' => 'Leert den Anwendungs-Cache. Nützlich, wenn Einstellungen oder Texte veraltet erscheinen.', 'command' => 'cache:clear', 'params' => [], 'risk' => self::RISK_LOW],
            'konfiguration-leeren' => ['group' => 'Zwischenspeicher', 'label' => 'Konfiguration leeren', 'description' => 'Verwirft die zwischengespeicherte Konfiguration (nach Änderungen an der .env).', 'command' => 'config:clear', 'params' => [], 'risk' => self::RISK_LOW],
            'ansichten-leeren' => ['group' => 'Zwischenspeicher', 'label' => 'Ansichten leeren', 'description' => 'Löscht die kompilierten Seitenvorlagen (nach Änderungen an Vorlagen).', 'command' => 'view:clear', 'params' => [], 'risk' => self::RISK_LOW],
            'alles-leeren' => ['group' => 'Zwischenspeicher', 'label' => 'Alle Zwischenspeicher leeren', 'description' => 'Leert Konfiguration, Routen, Ansichten und Cache in einem Schritt.', 'command' => 'optimize:clear', 'params' => [], 'risk' => self::RISK_LOW],
            'optimieren' => ['group' => 'Zwischenspeicher', 'label' => 'Für den Betrieb zwischenspeichern', 'description' => 'Baut Konfigurations-, Routen- und Ansichten-Cache für den Echtbetrieb. Nicht für die Entwicklung gedacht.', 'command' => 'optimize', 'params' => [], 'risk' => self::RISK_MEDIUM],

            'migrationen' => ['group' => 'Datenbank', 'label' => 'Migrationen ausführen', 'description' => 'Bringt die Datenbank auf den Stand der Anwendung (migrate --force). Macht nichts rückgängig und löscht keine Tabellen. Erstellen Sie vorher eine Sicherung.', 'command' => 'migrate', 'params' => ['--force' => true], 'risk' => self::RISK_HIGH],

            'mitgliedschaften-abgleichen' => ['group' => 'Geplante Aufgaben', 'label' => 'Mitgliedschaften abgleichen', 'description' => 'Beendet Mitgliedschaften mit abgelaufenem Austrittsdatum und gleicht die Rolle Vereinsmitglied ab (läuft täglich).', 'command' => 'membership:sync', 'params' => [], 'risk' => self::RISK_MEDIUM],
            'team-fristen' => ['group' => 'Geplante Aufgaben', 'label' => 'Fristen der Teambewerbung melden', 'description' => 'Informiert die Koordination über abgelaufene Fristen (versendet Benachrichtigungen, läuft täglich).', 'command' => 'seminars:team-deadlines', 'params' => [], 'risk' => self::RISK_MEDIUM],
            'einarbeitung-erinnern' => ['group' => 'Geplante Aufgaben', 'label' => 'Teamende an die Einarbeitung erinnern', 'description' => 'Versendet Erinnerungen an Teamende mit offener Einarbeitung (läuft täglich).', 'command' => 'onboarding:remind-team', 'params' => [], 'risk' => self::RISK_MEDIUM],
            'veranstaltungen-erinnern' => ['group' => 'Geplante Aufgaben', 'label' => 'Anmeldungen an Veranstaltungen erinnern', 'description' => 'Versendet Erinnerungen vor der ersten Sitzung (läuft täglich).', 'command' => 'events:remind', 'params' => [], 'risk' => self::RISK_MEDIUM],
            'schulen-erinnern' => ['group' => 'Geplante Aufgaben', 'label' => 'Schulen an Anmeldefristen erinnern', 'description' => 'Versendet Erinnerungen an Schulkonten (läuft täglich).', 'command' => 'events:remind-schools', 'params' => [], 'risk' => self::RISK_MEDIUM],

            'veranstaltungen-aufraeumen' => ['group' => 'Aufbewahrungsfristen', 'label' => 'Veranstaltungsdaten aufräumen', 'description' => 'Löscht Anmeldungen nach Ablauf der Aufbewahrungsfrist.', 'command' => 'events:prune', 'params' => [], 'risk' => self::RISK_HIGH],
            'mitgliedschaften-aufraeumen' => ['group' => 'Aufbewahrungsfristen', 'label' => 'Mitgliedschaftsdaten aufräumen', 'description' => 'Löscht unbestätigte Anträge, abgelehnte Anträge und beendete Mitgliedschaften nach den eingestellten Fristen.', 'command' => 'membership:prune', 'params' => [], 'risk' => self::RISK_HIGH],
            'zugangsantraege-aufraeumen' => ['group' => 'Aufbewahrungsfristen', 'label' => 'Zugangsanträge aufräumen', 'description' => 'Löscht entschiedene und alte offene Anträge samt Dateien.', 'command' => 'access-requests:prune', 'params' => [], 'risk' => self::RISK_HIGH],
            'protokoll-aufraeumen' => ['group' => 'Aufbewahrungsfristen', 'label' => 'Protokoll aufräumen', 'description' => 'Löscht Protokolleinträge nach Ablauf der Aufbewahrungsfrist.', 'command' => 'audit:prune', 'params' => [], 'risk' => self::RISK_HIGH],
            'konten-bereinigen' => ['group' => 'Aufbewahrungsfristen', 'label' => 'Gelöschte Konten anonymisieren', 'description' => 'Anonymisiert Konten, deren Löschfrist abgelaufen ist.', 'command' => 'account:purge', 'params' => [], 'risk' => self::RISK_HIGH],

            'wartung-an' => ['group' => 'Wartungsmodus', 'label' => 'Wartungsmodus einschalten', 'description' => 'Besucher sehen eine Wartungsseite. Sie behalten Zugang über einen geheimen Link, der nach dem Start geöffnet wird.', 'command' => 'down', 'params' => [], 'risk' => self::RISK_HIGH],
            'wartung-aus' => ['group' => 'Wartungsmodus', 'label' => 'Wartungsmodus beenden', 'description' => 'Das Portal ist wieder für alle erreichbar.', 'command' => 'up', 'params' => [], 'risk' => self::RISK_MEDIUM],
        ];
    }

    /** @return array{group: string, label: string, description: string, command: string, params: array<string, mixed>, risk: string}|null */
    public function find(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    public function needsPassword(string $key): bool
    {
        return ($this->find($key)['risk'] ?? null) === self::RISK_HIGH;
    }

    /** Der Wartungsmodus bietet je nach Zustand nur „einschalten“ oder „beenden“ an. */
    public function available(string $key): bool
    {
        return match ($key) {
            'wartung-an' => ! app()->isDownForMaintenance(),
            'wartung-aus' => app()->isDownForMaintenance(),
            default => true,
        };
    }

    /**
     * Führt die Aufgabe aus und hält den Lauf fest. Immer nur eine Aufgabe gleichzeitig.
     *
     * @throws DomainException wenn die Aufgabe unbekannt ist oder bereits eine läuft
     */
    public function run(string $key, User $by): SystemTaskRun
    {
        $task = $this->find($key) ?? throw new DomainException('Diese Aufgabe gibt es nicht.');

        if (! $this->available($key)) {
            throw new DomainException('Diese Aufgabe ist im aktuellen Zustand nicht möglich.');
        }

        $lock = Cache::lock('system-task', 300);

        if (! $lock->get()) {
            throw new DomainException('Es läuft bereits eine Aufgabe. Bitte warten Sie, bis sie beendet ist.');
        }

        $run = SystemTaskRun::query()->create(['task' => $key, 'user_id' => $by->id, 'status' => SystemTaskRun::RUNNING, 'started_at' => now()]);

        try {
            $params = $task['params'];
            $secret = null;

            if ($key === 'wartung-an') {
                $secret = Str::random(24);
                $params['--secret'] = $secret;
            }

            $code = Artisan::call($task['command'], $params);
            $output = trim(Artisan::output());

            if ($secret !== null) {
                $output .= "\nZugang für die Administration: ".url('/'.$secret);
            }
        } catch (Throwable $exception) {
            $code = 1;
            $output = 'Fehler: '.$exception->getMessage();
        } finally {
            $lock->release();
        }

        $run->forceFill([
            'status' => $code === 0 ? SystemTaskRun::SUCCESS : SystemTaskRun::FAILED,
            'exit_code' => $code,
            'output' => $this->clean($output),
            'finished_at' => now(),
        ])->save();

        AuditLog::record(AuditEvent::SystemTaskRun, $by, null, ['aufgabe' => $task['label'], 'ergebnis' => $code === 0 ? 'erfolgreich' : 'fehlgeschlagen']);

        SystemTaskRun::query()->where('started_at', '<', now()->subDays(90))->delete();

        return $run;
    }

    /** Geheimnisse der Konfiguration aus der Ausgabe entfernen und die Länge begrenzen. */
    private function clean(string $output): string
    {
        return Redactor::clean($output, 6000);
    }
}
