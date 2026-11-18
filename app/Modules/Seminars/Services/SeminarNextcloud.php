<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Nextcloud\NextcloudClient;
use DateTimeInterface;
use RuntimeException;
use Throwable;

/**
 * Nextcloud-Ordner zu Seminaren: Koordination/<Koordination>/<Schule>/<Schuljahr>/<Nummer Titel>/ und darin „Ergebnisse“, nur dieser Ordner wird für die Schule freigegeben.
 * Ohne Zugangsdaten bleibt alles beim Alten (Ordner von Hand). Ein Ausfall blockiert nichts; der Fehler steht am Seminar.
 */
final class SeminarNextcloud
{
    public const RESULTS = 'Ergebnisse';

    public function __construct(private readonly NextcloudClient $client, private readonly SeminarWorkflow $workflow) {}

    public function enabled(): bool
    {
        return $this->client->isConfigured();
    }

    /** Pfad des Seminarordners (ohne „Ergebnisse“). */
    public function path(Seminar $seminar): string
    {
        $seminar->loadMissing(['partner', 'coordination']);
        $team = $this->segment((string) config('services.nextcloud.team_folder', 'Koordination'), 'Koordination');

        return implode('/', [
            $team,
            $this->segment((string) ($seminar->coordination?->name ?? $seminar->partner->coordination?->name), 'Ohne Koordination'),
            $this->segment($seminar->partner->name, 'Schule'),
            $this->segment(str_replace('/', '-', (string) $seminar->school_year), 'Schuljahr'),
            $this->segment($seminar->number.' '.$seminar->title, 'Seminar'),
        ]);
    }

    /**
     * Legt alle Ordner bis zum Seminar und den Ordner „Ergebnisse“ an (wiederholbar). Fehler werden am Seminar vermerkt und nicht weitergegeben.
     */
    public function ensureFolders(Seminar $seminar): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $path = $this->path($seminar);
            $current = '';

            foreach (explode('/', $path.'/'.self::RESULTS) as $part) {
                $current = ltrim($current.'/'.$part, '/');
                $this->client->createFolder($current);
            }

            $seminar->forceFill(['nextcloud_path' => $path, 'nextcloud_synced_at' => now(), 'nextcloud_error' => null])->save();
            $this->workflow->log($seminar, 'nextcloud', null, null, null, 'Nextcloud-Ordner angelegt: '.$path);

            return true;
        } catch (Throwable $exception) {
            report($exception);
            $seminar->forceFill(['nextcloud_error' => mb_substr($exception->getMessage(), 0, 500)])->save();
            $this->workflow->log($seminar, 'nextcloud_failed', null, null, null, 'Der Nextcloud-Ordner konnte nicht angelegt werden. Bitte legen Sie ihn von Hand an oder versuchen Sie es erneut.');

            return false;
        }
    }

    /**
     * Link auf den Ordner „Ergebnisse“ mit Ablaufdatum. Gibt es schon einen Link, wird nur das Datum geändert.
     *
     * @return array{id: string, url: string}|null null, wenn die Nextcloud nicht eingerichtet ist
     *
     * @throws RuntimeException
     */
    public function share(Seminar $seminar, DateTimeInterface $expires): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        if ($seminar->nextcloud_path === null && ! $this->ensureFolders($seminar)) {
            throw new RuntimeException('Der Nextcloud-Ordner fehlt und ließ sich nicht anlegen: '.$seminar->nextcloud_error);
        }

        $expireDate = $expires->format('Y-m-d');

        if (filled($seminar->nextcloud_share_id)) {
            $this->client->updateShareExpiry((string) $seminar->nextcloud_share_id, $expireDate);

            return ['id' => (string) $seminar->nextcloud_share_id, 'url' => (string) $seminar->results_url];
        }

        $share = $this->client->createPublicShare($seminar->nextcloud_path.'/'.self::RESULTS, $expireDate);
        $seminar->forceFill(['nextcloud_share_id' => $share['id']])->save();

        return $share;
    }

    /** Ein Ordnername ohne Zeichen, die Nextcloud oder Windows nicht mögen. */
    private function segment(string $name, string $fallback): string
    {
        $clean = trim((string) preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', ' ', $name));
        $clean = trim((string) preg_replace('/\s+/u', ' ', $clean), " .\t");

        return mb_substr($clean === '' ? $fallback : $clean, 0, 80);
    }
}
