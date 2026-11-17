<?php

namespace App\Modules\Seminars\Nextcloud;

/** Schnittstelle zur Nextcloud für die Seminarordner. Die echte Umsetzung spricht WebDAV und die OCS-Schnittstellen an, in Tests steht eine Attrappe. */
interface NextcloudClient
{
    /** Ist die Nextcloud-Anbindung eingerichtet? */
    public function enabled(): bool;

    /** Legt einen Ordner samt aller fehlenden Elternordner an (vorhandene bleiben unverändert). */
    public function ensureFolder(string $path): void;

    /**
     * Setzt die Zugriffsregeln (ACL der Team folders) des Ordners: nur diese Konten bekommen Zugriff. Frühere Regeln dieses Ordners werden ersetzt.
     *
     * @param  list<string>  $uids  Nextcloud-Kennungen
     */
    public function grantUsers(string $path, array $uids): void;

    /** Öffentlicher Lese-Link auf einen Ordner mit Ablaufdatum. @return array{id: string, url: string} */
    public function createShare(string $path, string $expires): array;

    public function setShareExpiry(string $shareId, string $expires): void;

    public function deleteShare(string $shareId): void;
}
