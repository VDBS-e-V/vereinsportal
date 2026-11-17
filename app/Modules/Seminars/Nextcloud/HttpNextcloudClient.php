<?php

namespace App\Modules\Seminars\Nextcloud;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Die Anbindung an die Nextcloud: Ordner über WebDAV, Zugriffsregeln der Team folders per PROPPATCH (acl-list), öffentliche Links über die OCS-Schnittstelle
 * (Dateien teilen). Das Dienstkonto braucht in den Team folders das Recht, Zugriffsregeln zu verwalten. Das Passwort steht nur in der Umgebung und nie im Protokoll.
 */
final class HttpNextcloudClient implements NextcloudClient
{
    public function __construct(private readonly string $url, private readonly string $user, private readonly string $password, private readonly int $timeout = 20) {}

    public function enabled(): bool
    {
        return $this->url !== '' && $this->user !== '' && $this->password !== '';
    }

    public function ensureFolder(string $path): void
    {
        $current = '';

        foreach (array_filter(explode('/', trim($path, '/')), fn (string $part): bool => $part !== '') as $segment) {
            $current .= '/'.$segment;
            $status = $this->request()->send('MKCOL', $this->dav($current))->status();

            // 201 angelegt, 405 gibt es schon
            if (! in_array($status, [201, 405], true)) {
                throw new RuntimeException('Der Ordner „'.$segment.'“ konnte nicht angelegt werden (Antwort '.$status.').');
            }
        }
    }

    public function grantUsers(string $path, array $uids): void
    {
        $rules = '';

        foreach ($uids as $uid) {
            $id = htmlspecialchars($uid, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $rules .= '<nc:acl><nc:acl-mapping-type>user</nc:acl-mapping-type><nc:acl-mapping-id>'.$id.'</nc:acl-mapping-id><nc:acl-mapping-display-name>'.$id.'</nc:acl-mapping-display-name><nc:acl-mask>31</nc:acl-mask><nc:acl-permissions>31</nc:acl-permissions></nc:acl>';
        }

        $body = '<?xml version="1.0" encoding="UTF-8"?><d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns"><d:set><d:prop><nc:acl-list>'.$rules.'</nc:acl-list></d:prop></d:set></d:propertyupdate>';
        $status = $this->request()->withBody($body, 'application/xml')->send('PROPPATCH', $this->dav('/'.trim($path, '/')))->status();

        if (! in_array($status, [200, 207], true)) {
            throw new RuntimeException('Die Zugriffsregeln konnten nicht gesetzt werden (Antwort '.$status.'). Prüfen Sie, ob die erweiterten Rechte (ACL) des Team folders aktiv sind.');
        }
    }

    public function createShare(string $path, string $expires): array
    {
        $response = $this->ocs()->asForm()->post($this->ocsUrl('/shares'), ['path' => '/'.trim($path, '/'), 'shareType' => 3, 'permissions' => 1, 'expireDate' => $expires]);
        $data = $response->json('ocs.data');

        if (! $response->successful() || (int) $response->json('ocs.meta.statuscode') !== 200 || ! is_array($data) || empty($data['id']) || empty($data['url'])) {
            throw new RuntimeException('Der Ergebnislink konnte nicht erzeugt werden (Antwort '.$response->status().').');
        }

        return ['id' => (string) $data['id'], 'url' => (string) $data['url']];
    }

    public function setShareExpiry(string $shareId, string $expires): void
    {
        $response = $this->ocs()->asForm()->put($this->ocsUrl('/shares/'.rawurlencode($shareId)), ['expireDate' => $expires]);

        if (! $response->successful()) {
            throw new RuntimeException('Das Ablaufdatum des Links konnte nicht geändert werden (Antwort '.$response->status().').');
        }
    }

    public function deleteShare(string $shareId): void
    {
        $this->ocs()->delete($this->ocsUrl('/shares/'.rawurlencode($shareId)));
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth($this->user, $this->password)->timeout($this->timeout)->withHeaders(['OCS-APIRequest' => 'true']);
    }

    private function ocs(): PendingRequest
    {
        return $this->request()->acceptJson();
    }

    private function dav(string $path): string
    {
        $segments = array_map('rawurlencode', array_values(array_filter(explode('/', $path), fn (string $part): bool => $part !== '')));

        return rtrim($this->url, '/').'/remote.php/dav/files/'.rawurlencode($this->user).'/'.implode('/', $segments);
    }

    private function ocsUrl(string $suffix): string
    {
        return rtrim($this->url, '/').'/ocs/v2.php/apps/files_sharing/api/v1'.$suffix.'?format=json';
    }
}
