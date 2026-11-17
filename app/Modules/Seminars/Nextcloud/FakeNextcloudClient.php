<?php

namespace App\Modules\Seminars\Nextcloud;

/** Attrappe für Tests: merkt sich Ordner, Zugriffe und Links und kann Fehler vortäuschen. */
final class FakeNextcloudClient implements NextcloudClient
{
    /** @var list<string> */
    public array $folders = [];

    /** @var array<string, list<string>> */
    public array $access = [];

    /** @var array<string, array{path: string, expires: string}> */
    public array $shares = [];

    public bool $fail = false;

    public function enabled(): bool
    {
        return true;
    }

    public function ensureFolder(string $path): void
    {
        $this->check();
        $this->folders[] = $path;
    }

    public function grantUsers(string $path, array $uids): void
    {
        $this->check();
        $this->access[$path] = $uids;
    }

    public function createShare(string $path, string $expires): array
    {
        $this->check();
        $id = (string) (count($this->shares) + 1);
        $this->shares[$id] = ['path' => $path, 'expires' => $expires];

        return ['id' => $id, 'url' => 'https://cloud.example.org/s/'.$id];
    }

    public function setShareExpiry(string $shareId, string $expires): void
    {
        $this->check();
        $this->shares[$shareId]['expires'] = $expires;
    }

    public function deleteShare(string $shareId): void
    {
        $this->check();
        unset($this->shares[$shareId]);
    }

    private function check(): void
    {
        if ($this->fail) {
            throw new \RuntimeException('Nextcloud nicht erreichbar');
        }
    }
}
