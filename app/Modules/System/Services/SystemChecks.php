<?php

namespace App\Modules\System\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Zustandsprüfungen für die Systemsteuerung (nur lesen). Jede Prüfung liefert Gruppe, Bezeichnung, Status und einen erklärenden Satz.
 * Status: ok | warning | error | info. Eine Prüfung darf nie selbst scheitern: Fehler werden zum Status „error“.
 */
final class SystemChecks
{
    public const HEARTBEAT_KEY = 'system.heartbeat';

    /** @return list<array{group: string, label: string, status: string, detail: string}> */
    public function all(): array
    {
        return array_merge(
            $this->environment(),
            $this->php(),
            $this->database(),
            $this->storage(),
            $this->services(),
        );
    }

    /** @return array{ok: int, warning: int, error: int} */
    public function counts(): array
    {
        $counts = ['ok' => 0, 'warning' => 0, 'error' => 0];

        foreach ($this->all() as $check) {
            if (isset($counts[$check['status']])) {
                $counts[$check['status']]++;
            }
        }

        return $counts;
    }

    /** Minuten seit dem letzten Lauf des Planers, null wenn noch nie. */
    public function schedulerAgeMinutes(): ?int
    {
        $timestamp = Cache::get(self::HEARTBEAT_KEY);

        return is_int($timestamp) ? max(0, (int) floor((time() - $timestamp) / 60)) : null;
    }

    /** Anzahl ausstehender Migrationen. */
    public function pendingMigrations(): int
    {
        try {
            $migrator = app('migrator');
            $repository = $migrator->getRepository();
            $files = array_keys($migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths())));
            $ran = $repository->repositoryExists() ? $repository->getRan() : [];

            return count(array_diff($files, $ran));
        } catch (Throwable) {
            return -1;
        }
    }

    /** @return list<array{group: string, label: string, status: string, detail: string}> */
    private function environment(): array
    {
        $env = (string) config('app.env');
        $debug = (bool) config('app.debug');
        $production = $env === 'production';

        return [
            $this->check('Anwendung', 'Umgebung', $production ? 'ok' : 'info', 'APP_ENV = '.$env.($production ? '' : ' (Entwicklung oder Test)')),
            $this->check('Anwendung', 'Debug-Modus', $debug && $production ? 'error' : ($debug ? 'info' : 'ok'), $debug ? ($production ? 'APP_DEBUG ist im Echtbetrieb eingeschaltet: Fehlerseiten zeigen interne Angaben. Bitte ausschalten.' : 'Eingeschaltet (in der Entwicklung üblich).') : 'Ausgeschaltet.'),
            $this->check('Anwendung', 'Wartungsmodus', app()->isDownForMaintenance() ? 'warning' : 'ok', app()->isDownForMaintenance() ? 'Das Portal ist für Besucher gesperrt.' : 'Aus, das Portal ist erreichbar.'),
            $this->check('Anwendung', 'Adresse', filled(config('app.url')) ? 'ok' : 'warning', 'APP_URL = '.(config('app.url') ?: 'nicht gesetzt')),
            $this->check('Anwendung', 'Versionen', 'info', 'PHP '.PHP_VERSION.', Laravel '.app()->version()),
        ];
    }

    /** @return list<array{group: string, label: string, status: string, detail: string}> */
    private function php(): array
    {
        $checks = [];
        $required = ['mbstring' => 'Textverarbeitung', 'openssl' => 'Verschlüsselung', 'pdo' => 'Datenbank', 'fileinfo' => 'Dateitypen', 'ctype' => 'Zeichenprüfung', 'json' => 'JSON', 'dom' => 'Rich-Text-Bereinigung', 'tokenizer' => 'Vorlagen'];

        foreach ($required as $extension => $purpose) {
            if (! extension_loaded($extension)) {
                $checks[] = $this->check('PHP', 'Erweiterung '.$extension, 'error', 'Fehlt ('.$purpose.'). Bitte in der php.ini aktivieren.');
            }
        }

        if ($checks === []) {
            $checks[] = $this->check('PHP', 'Pflicht-Erweiterungen', 'ok', 'Alle vorhanden.');
        }

        $checks[] = extension_loaded('gd')
            ? $this->check('PHP', 'Erweiterung gd', 'ok', 'Vorhanden: Profilfotos werden zugeschnitten und verkleinert.')
            : $this->check('PHP', 'Erweiterung gd', 'warning', 'Fehlt: Profilfotos werden unverändert gespeichert. In der php.ini extension=gd aktivieren.');

        return $checks;
    }

    /** @return list<array{group: string, label: string, status: string, detail: string}> */
    private function database(): array
    {
        try {
            DB::connection()->getPdo();
            $name = (string) config('database.default');
            $checks = [$this->check('Datenbank', 'Verbindung', 'ok', 'Verbunden ('.$name.').')];
        } catch (Throwable $exception) {
            return [$this->check('Datenbank', 'Verbindung', 'error', 'Keine Verbindung zur Datenbank.')];
        }

        $pending = $this->pendingMigrations();
        $checks[] = match (true) {
            $pending < 0 => $this->check('Datenbank', 'Migrationen', 'warning', 'Der Stand konnte nicht geprüft werden.'),
            $pending === 0 => $this->check('Datenbank', 'Migrationen', 'ok', 'Die Datenbank ist auf dem aktuellen Stand.'),
            default => $this->check('Datenbank', 'Migrationen', 'error', $pending.' Migration'.($pending === 1 ? '' : 'en').' ausstehend. Unter Aufgaben „Migrationen ausführen“ starten.'),
        };

        return $checks;
    }

    /** @return list<array{group: string, label: string, status: string, detail: string}> */
    private function storage(): array
    {
        $checks = [];

        foreach (['storage/app' => storage_path('app'), 'storage/logs' => storage_path('logs'), 'storage/framework' => storage_path('framework'), 'bootstrap/cache' => base_path('bootstrap/cache')] as $label => $path) {
            $checks[] = is_dir($path) && is_writable($path)
                ? $this->check('Dateien', $label, 'ok', 'Beschreibbar.')
                : $this->check('Dateien', $label, 'error', 'Nicht beschreibbar. Der Webserver braucht hier Schreibrechte.');
        }

        $free = @disk_free_space(storage_path());

        if ($free !== false) {
            $gb = round($free / 1024 ** 3, 1);
            $checks[] = $this->check('Dateien', 'Freier Speicher', $gb < 1 ? 'error' : ($gb < 5 ? 'warning' : 'ok'), $gb.' GB frei.');
        }

        $log = storage_path('logs/laravel.log');

        if (is_file($log)) {
            $mb = round(filesize($log) / 1024 ** 2, 1);
            $checks[] = $this->check('Dateien', 'Protokolldatei', $mb > 100 ? 'warning' : 'ok', $mb.' MB (storage/logs/laravel.log).');
        }

        $checks[] = is_file(public_path('build/manifest.json'))
            ? $this->check('Dateien', 'Frontend gebaut', 'ok', 'public/build ist vorhanden.')
            : $this->check('Dateien', 'Frontend gebaut', 'error', 'public/build/manifest.json fehlt: npm run build ausführen.');

        return $checks;
    }

    /** @return list<array{group: string, label: string, status: string, detail: string}> */
    private function services(): array
    {
        $checks = [];
        $mailer = (string) config('mail.default');
        $checks[] = in_array($mailer, ['log', 'array'], true)
            ? $this->check('Dienste', 'E-Mail-Versand', config('app.env') === 'production' ? 'error' : 'info', 'Treiber „'.$mailer.'“: E-Mails werden nicht zugestellt, nur protokolliert.')
            : $this->check('Dienste', 'E-Mail-Versand', 'ok', 'Treiber „'.$mailer.'“.');

        $age = $this->schedulerAgeMinutes();
        $checks[] = match (true) {
            $age === null => $this->check('Dienste', 'Planer (Scheduler)', 'warning', 'Noch nie gelaufen. Auf dem Server fehlt der Cron-Eintrag „php artisan schedule:run“ jede Minute (lokal: php artisan schedule:work).'),
            $age > 10 => $this->check('Dienste', 'Planer (Scheduler)', 'warning', 'Der letzte Lauf war vor '.$age.' Minuten. Die täglichen Aufgaben laufen nicht zuverlässig.'),
            default => $this->check('Dienste', 'Planer (Scheduler)', 'ok', 'Läuft (letzter Lauf vor '.$age.' Min.).'),
        };

        $queue = (string) config('queue.default');
        $detail = 'Treiber „'.$queue.'“.';
        $status = 'info';

        try {
            if ($queue === 'database' && Schema::hasTable('jobs')) {
                $waiting = DB::table('jobs')->count();
                $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
                $detail .= ' '.$waiting.' wartend, '.$failed.' fehlgeschlagen.';
                $status = $failed > 0 ? 'warning' : 'ok';
                $oldest = DB::table('jobs')->where('available_at', '<=', time())->min('created_at');

                if ($oldest !== null && time() - (int) $oldest > 300) {
                    $detail .= ' Der älteste wartende Auftrag ist '.(int) floor((time() - (int) $oldest) / 60).' Minuten alt: Läuft der Worker (php artisan queue:work)?';
                    $status = 'warning';
                }
            } elseif ($queue === 'sync') {
                $detail .= ' Aufträge laufen sofort im Seitenaufruf (kein Worker nötig, aber große Schreiben können das Zeitlimit erreichen).';
            }
        } catch (Throwable) {
            $status = 'warning';
        }

        $checks[] = $this->check('Dienste', 'Warteschlange', $status, $detail);

        try {
            if (Schema::hasTable('mailings')) {
                $stuck = DB::table('mailings')->whereIn('status', ['queued', 'sending'])->where('queued_at', '<', now()->subMinutes(15))->count();
                $checks[] = $stuck > 0
                    ? $this->check('Dienste', 'Schreiben an Gruppen', 'warning', $stuck.' Schreiben warten seit über 15 Minuten auf den Versand. Läuft der Worker?')
                    : $this->check('Dienste', 'Schreiben an Gruppen', 'ok', 'Keine Schreiben im Rückstand.');
            }
        } catch (Throwable) {
            // Die Prüfung darf nie selbst scheitern.
        }

        return $checks;
    }

    /** @return array{group: string, label: string, status: string, detail: string} */
    private function check(string $group, string $label, string $status, string $detail): array
    {
        return ['group' => $group, 'label' => $label, 'status' => $status, 'detail' => $detail];
    }
}
