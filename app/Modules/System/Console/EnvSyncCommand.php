<?php

namespace App\Modules\System\Console;

use Illuminate\Console\Command;

/**
 * Ergänzt die .env um Einstellungen, die in der .env.example neu dazugekommen sind. Vorhandene Werte werden nie verändert,
 * nichts wird gelöscht. Vor dem Schreiben entsteht eine Sicherungskopie der .env. Auskommentierte Zeilen der Vorlage gelten als Hinweis und werden nicht übernommen.
 */
class EnvSyncCommand extends Command
{
    protected $signature = 'env:sync {--dry-run : Nur anzeigen, was ergänzt würde} {--env= : Pfad der .env (Standard: Projektordner)} {--example= : Pfad der .env.example (Standard: Projektordner)}';

    protected $description = 'Ergänzt fehlende Einstellungen der .env anhand der .env.example (ohne vorhandene Werte zu ändern).';

    public function handle(): int
    {
        $envPath = (string) ($this->option('env') ?: base_path('.env'));
        $examplePath = (string) ($this->option('example') ?: base_path('.env.example'));

        if (! is_file($examplePath)) {
            $this->components->error('Die Vorlage '.$examplePath.' fehlt.');

            return self::FAILURE;
        }

        if (! is_file($envPath)) {
            $this->components->error('Es gibt noch keine .env. Kopieren Sie zuerst die .env.example nach .env.');

            return self::FAILURE;
        }

        $env = (string) file_get_contents($envPath);
        $existing = $this->keys($env);
        $missing = $this->missing((string) file_get_contents($examplePath), $existing);

        $example = $this->keys((string) file_get_contents($examplePath));
        $extra = array_values(array_diff($existing, $example));

        if ($missing === []) {
            $this->components->info('Die .env enthält bereits alle Einstellungen der Vorlage.');
        } else {
            $this->line('Fehlende Einstellungen:');

            foreach ($missing as $item) {
                $this->line('  + '.$item['key']);
            }
        }

        if ($extra !== []) {
            $this->line('');
            $this->line('Nur in Ihrer .env (bleiben unverändert): '.implode(', ', $extra));
        }

        if ($missing === [] || $this->option('dry-run')) {
            if ($missing !== [] && $this->option('dry-run')) {
                $this->components->info('Probelauf: Es wurde nichts geschrieben.');
            }

            return self::SUCCESS;
        }

        $backup = $envPath.'.backup-'.date('Ymd-His');

        if (! copy($envPath, $backup)) {
            $this->components->error('Die Sicherungskopie konnte nicht angelegt werden. Es wurde nichts geändert.');

            return self::FAILURE;
        }

        $block = "\n# --- ergänzt am ".date('d.m.Y H:i')." (env:sync) ---\n";

        foreach ($missing as $item) {
            $block .= ($item['comment'] !== '' ? $item['comment']."\n" : '').$item['line']."\n";
        }

        file_put_contents($envPath, rtrim($env, "\r\n")."\n".$block);

        $this->components->info(count($missing).' Einstellung(en) ergänzt. Sicherung: '.basename($backup));
        $this->line('Prüfen Sie die ergänzten Werte (am Ende der Datei) und führen Sie danach "php artisan config:clear" aus.');

        return self::SUCCESS;
    }

    /** @return list<string> Schlüssel der aktiven Zeilen */
    private function keys(string $content): array
    {
        preg_match_all('/^\s*([A-Z][A-Z0-9_]*)\s*=/m', $content, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param  list<string>  $existing
     * @return list<array{key: string, line: string, comment: string}>
     */
    private function missing(string $example, array $existing): array
    {
        $result = [];
        $comment = [];

        foreach (preg_split('/\R/', $example) ?: [] as $line) {
            if (preg_match('/^\s*#/', $line) === 1) {
                $comment[] = $line;

                continue;
            }

            if (preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*=/', $line, $match) === 1) {
                if (! in_array($match[1], $existing, true)) {
                    $result[] = ['key' => $match[1], 'line' => rtrim($line), 'comment' => implode("\n", $comment)];
                    $existing[] = $match[1];
                }
            }

            $comment = [];
        }

        return $result;
    }
}
