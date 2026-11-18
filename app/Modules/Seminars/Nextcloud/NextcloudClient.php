<?php

namespace App\Modules\Seminars\Nextcloud;

use RuntimeException;

/** Schnittstelle zur Nextcloud: Ordner anlegen und den Ordner „Ergebnisse“ mit einem Link (Ablaufdatum) freigeben. */
interface NextcloudClient
{
    /** Sind Adresse, Dienstkonto und Passwort eingetragen? Ohne Zugangsdaten bleibt alles beim Alten (Ordner von Hand). */
    public function isConfigured(): bool;

    /**
     * Legt einen Ordner an. Gibt es ihn schon, ist das kein Fehler.
     *
     * @throws RuntimeException
     */
    public function createFolder(string $path): void;

    /**
     * Gibt einen Ordner per öffentlichem Link frei (nur lesen) und gibt Kennung und Adresse zurück.
     *
     * @return array{id: string, url: string}
     *
     * @throws RuntimeException
     */
    public function createPublicShare(string $path, string $expireDate): array;

    /** @throws RuntimeException */
    public function updateShareExpiry(string $shareId, string $expireDate): void;
}
