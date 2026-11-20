<?php

namespace App\Modules\Seminars\Nextcloud;

use RuntimeException;

/** Attrappe für Tests: merkt sich Ordner und Freigaben, kann Fehler vortäuschen. */
final class FakeNextcloudClient implements NextcloudClient
{
    /** @var list<string> */
    public array $folders = [];

    /** @var array<string, array{path: string, expires: string}> */
    public array $shares = [];

    public bool $failing = false;

    public function isConfigured(): bool
    {
        return true;
    }

    public function createFolder(string $path): void
    {
        $this->guard();
        $this->folders[] = $path;
    }

    public function createPublicShare(string $path, string $expireDate): array
    {
        $this->guard();
        $id = (string) (count($this->shares) + 1);
        $this->shares[$id] = ['path' => $path, 'expires' => $expireDate];

        return ['id' => $id, 'url' => 'https://cloud.example/s/fake'.$id];
    }

    public function updateShareExpiry(string $shareId, string $expireDate): void
    {
        $this->guard();
        $this->shares[$shareId]['expires'] = $expireDate;
    }

    private function guard(): void
    {
        if ($this->failing) {
            throw new RuntimeException('Nextcloud: nicht erreichbar.');
        }
    }
}
