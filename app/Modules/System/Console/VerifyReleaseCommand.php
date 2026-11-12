<?php

namespace App\Modules\System\Console;

use Illuminate\Console\Command;

/**
 * Prüft nach dem FTP-Upload, ob alle Dateien des Pakets vollständig angekommen sind (release-manifest.json im Hauptordner). Alle Dateien werden auf
 * Vorhandensein und Größe geprüft, die Dateien außerhalb von vendor/ zusätzlich auf die Prüfsumme.
 */
class VerifyReleaseCommand extends Command
{
    protected $signature = 'portal:verify-release';

    protected $description = 'Prüft die hochgeladenen Dateien gegen die Prüfsummen des Pakets.';

    public function handle(): int
    {
        $path = base_path('release-manifest.json');

        if (! is_file($path)) {
            $this->components->warn('Es gibt kein release-manifest.json (kein Paket aus build-release).');

            return self::FAILURE;
        }

        $manifest = json_decode((string) file_get_contents($path), true);
        $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
        $missing = [];
        $changed = [];

        foreach ($files as $relative => $info) {
            $file = base_path($relative);

            if (! is_file($file)) {
                $missing[] = $relative;

                continue;
            }

            if (filesize($file) !== (int) ($info['size'] ?? -1)) {
                $changed[] = $relative;

                continue;
            }

            if (! str_starts_with($relative, 'vendor/') && hash_file('sha256', $file) !== ($info['sha256'] ?? '')) {
                $changed[] = $relative;
            }
        }

        $this->line('Version '.($manifest['version'] ?? '?').', '.count($files).' Dateien geprüft.');

        foreach (array_slice($missing, 0, 20) as $relative) {
            $this->line('FEHLT: '.$relative);
        }

        foreach (array_slice($changed, 0, 20) as $relative) {
            $this->line('ANDERS: '.$relative);
        }

        if ($missing === [] && $changed === []) {
            $this->components->info('Alle Dateien sind vollständig und unverändert.');

            return self::SUCCESS;
        }

        $this->components->error(count($missing).' fehlen, '.count($changed).' weichen ab. Bitte die genannten Dateien erneut hochladen.');

        return self::FAILURE;
    }
}
