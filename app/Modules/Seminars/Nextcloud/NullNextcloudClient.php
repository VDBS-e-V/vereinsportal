<?php

namespace App\Modules\Seminars\Nextcloud;

use DomainException;

/** Ohne Anbindung: nichts wird angelegt, jeder Aufruf erklärt, was fehlt. */
final class NullNextcloudClient implements NextcloudClient
{
    public function enabled(): bool
    {
        return false;
    }

    public function ensureFolder(string $path): void
    {
        $this->fail();
    }

    public function grantUsers(string $path, array $uids): void
    {
        $this->fail();
    }

    public function createShare(string $path, string $expires): array
    {
        $this->fail();
    }

    public function setShareExpiry(string $shareId, string $expires): void
    {
        $this->fail();
    }

    public function deleteShare(string $shareId): void
    {
        $this->fail();
    }

    private function fail(): never
    {
        throw new DomainException('Die Nextcloud-Anbindung ist nicht eingerichtet (NEXTCLOUD_URL, NEXTCLOUD_SERVICE_USER, NEXTCLOUD_SERVICE_PASSWORD).');
    }
}
