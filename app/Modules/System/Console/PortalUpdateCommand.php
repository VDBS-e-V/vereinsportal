<?php

namespace App\Modules\System\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Das eine sichere Update nach dem Hochladen einer neuen Version: Paket prüfen, Wartungsmodus an, Datenbank migrieren,
 * Zwischenspeicher leeren, Wartungsmodus aus. Scheitert die Migration, bleibt das Portal absichtlich in der Wartung
 * (Besucher sehen nur die Wartungsseite statt halb migrierter Daten); der geheime Zugangslink wird ausgegeben.
 * Eine Sicherung der Datenbank gehört vorher dazu und wird hier nicht ersetzt.
 */
class PortalUpdateCommand extends Command
{
    protected $signature = 'portal:update {--skip-verify : Paketprüfung überspringen}';

    protected $description = 'Führt das Update nach einem Upload aus: prüfen, Wartung an, migrieren, Cache leeren, Wartung aus.';

    public function handle(): int
    {
        if (! $this->option('skip-verify') && is_file(base_path('release-manifest.json'))) {
            $this->components->info('1/5 Paket prüfen');

            if ($this->call('portal:verify-release') !== self::SUCCESS) {
                $this->components->error('Abgebrochen: Das Paket ist nicht vollständig. Es wurde nichts verändert und das Portal läuft weiter.');

                return self::FAILURE;
            }
        } else {
            $this->line('1/5 Paketprüfung übersprungen (kein release-manifest.json).');
        }

        $this->components->info('2/5 Datenbank prüfen');

        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            $this->components->error('Abgebrochen: Keine Verbindung zur Datenbank ('.$exception->getMessage().'). Es wurde nichts verändert.');

            return self::FAILURE;
        }

        $wasDown = app()->isDownForMaintenance();
        $secret = null;

        if (! $wasDown) {
            $this->components->info('3/5 Wartungsmodus einschalten');
            $secret = Str::random(24);
            $this->callSilently('down', ['--secret' => $secret]);
        } else {
            $this->line('3/5 Wartungsmodus war bereits an und bleibt es bis zum Ende.');
        }

        $this->components->info('4/5 Datenbank aktualisieren');

        try {
            $code = $this->call('migrate', ['--force' => true]);
        } catch (Throwable $exception) {
            $this->line('Fehler: '.$exception->getMessage());
            $code = self::FAILURE;
        }

        if ($code !== self::SUCCESS) {
            $this->components->error('Die Migration ist fehlgeschlagen. Das Portal bleibt in der Wartung.');

            if ($secret !== null) {
                $this->line('Zugang für die Administration: '.url('/'.$secret));
            }

            $this->line('Nächste Schritte: Meldung oben prüfen, bei Bedarf die Sicherung einspielen, dann diese Aufgabe erneut starten.');

            return self::FAILURE;
        }

        $this->components->info('5/5 Zwischenspeicher leeren und Wartungsmodus beenden');
        $this->callSilently('optimize:clear');

        if (! $wasDown) {
            $this->callSilently('up');
        }

        $this->components->info('Update abgeschlossen.');

        return self::SUCCESS;
    }
}
