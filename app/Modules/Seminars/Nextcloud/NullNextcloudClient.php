<?php

namespace App\Modules\Seminars\Nextcloud;

use RuntimeException;

/** Ohne Zugangsdaten: tut nichts. Das Portal zeigt dann „Ordner von Hand anlegen“ und blockiert nichts. */
final class NullNextcloudClient implements NextcloudClient
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function createFolder(string $path): void
    {
        throw new RuntimeException('Die Nextcloud ist nicht eingerichtet.');
    }

    public function createPublicShare(string $path, string $expireDate): array
    {
        throw new RuntimeException('Die Nextcloud ist nicht eingerichtet.');
    }

    public function updateShareExpiry(string $shareId, string $expireDate): void
    {
        throw new RuntimeException('Die Nextcloud ist nicht eingerichtet.');
    }
}
