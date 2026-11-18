<?php

namespace App\Modules\Seminars\Nextcloud;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Echte Nextcloud: WebDAV für Ordner, OCS-Schnittstelle für den Freigabelink. Anmeldung mit einem Dienstkonto und App-Passwort aus der Umgebung. */
final class HttpNextcloudClient implements NextcloudClient
{
    public function __construct(private readonly string $baseUrl, private readonly string $user, private readonly string $password) {}

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->user !== '' && $this->password !== '';
    }

    public function createFolder(string $path): void
    {
        $response = $this->request()->send('MKCOL', $this->davUrl($path));

        // 201 angelegt, 405 gibt es schon
        if (! in_array($response->status(), [201, 405], true)) {
            throw new RuntimeException('Nextcloud: Ordner „'.$path.'“ konnte nicht angelegt werden (Antwort '.$response->status().').');
        }
    }

    public function createPublicShare(string $path, string $expireDate): array
    {
        $response = $this->request()->asForm()->post($this->ocsUrl('shares'), ['path' => '/'.trim($path, '/'), 'shareType' => 3, 'permissions' => 1, 'expireDate' => $expireDate]);
        $data = $response->json('ocs.data');

        if (! $response->successful() || ! is_array($data) || blank($data['url'] ?? null) || blank($data['id'] ?? null)) {
            throw new RuntimeException('Nextcloud: Der Freigabelink konnte nicht erstellt werden (Antwort '.$response->status().').');
        }

        return ['id' => (string) $data['id'], 'url' => (string) $data['url']];
    }

    public function updateShareExpiry(string $shareId, string $expireDate): void
    {
        $response = $this->request()->asForm()->put($this->ocsUrl('shares/'.rawurlencode($shareId)), ['expireDate' => $expireDate]);

        if (! $response->successful()) {
            throw new RuntimeException('Nextcloud: Das Ablaufdatum konnte nicht geändert werden (Antwort '.$response->status().').');
        }
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth($this->user, $this->password)->withHeaders(['OCS-APIRequest' => 'true', 'Accept' => 'application/json'])->timeout(20)->retry(2, 300, throw: false);
    }

    private function davUrl(string $path): string
    {
        $segments = array_map('rawurlencode', array_filter(explode('/', trim($path, '/')), fn (string $part): bool => $part !== ''));

        return rtrim($this->baseUrl, '/').'/remote.php/dav/files/'.rawurlencode($this->user).'/'.implode('/', $segments);
    }

    private function ocsUrl(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/').'/ocs/v2.php/apps/files_sharing/api/v1/'.$endpoint.'?format=json';
    }
}
