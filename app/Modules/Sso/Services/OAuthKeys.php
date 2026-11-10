<?php

namespace App\Modules\Sso\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use DomainException;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

/** Schlüsselpaar, mit dem das Portal Anmeldungen für andere Systeme signiert: Zustand ansehen, fehlende Schlüssel erzeugen, erneuern. Der private Schlüssel wird nie angezeigt. */
final class OAuthKeys
{
    public function privatePath(): string
    {
        return Passport::keyPath('oauth-private.key');
    }

    public function publicPath(): string
    {
        return Passport::keyPath('oauth-public.key');
    }

    public function exists(): bool
    {
        return is_file($this->privatePath()) && is_file($this->publicPath());
    }

    /** @return array{exists: bool, fingerprint: ?string, created_at: ?\Illuminate\Support\Carbon} */
    public function status(): array
    {
        if (! $this->exists()) {
            return ['exists' => false, 'fingerprint' => null, 'created_at' => null];
        }

        $hash = strtoupper(hash('sha256', (string) file_get_contents($this->publicPath())));

        return ['exists' => true, 'fingerprint' => implode(':', str_split(substr($hash, 0, 24), 4)), 'created_at' => \Illuminate\Support\Carbon::createFromTimestamp((int) filemtime($this->publicPath()))];
    }

    /** Kennung des öffentlichen Schlüssels (kid in JWKS und ID-Token). */
    public function keyId(): ?string
    {
        return $this->exists() ? substr(hash('sha256', (string) file_get_contents($this->publicPath())), 0, 16) : null;
    }

    /** Öffentlicher Schlüssel als JWK (RS256) oder null, wenn keiner da ist. @return array<string, string>|null */
    public function jwk(): ?array
    {
        if (! $this->exists()) {
            return null;
        }

        $key = openssl_pkey_get_public((string) file_get_contents($this->publicPath()));
        $details = $key ? openssl_pkey_get_details($key) : false;

        if (! $details || ! isset($details['rsa']['n'], $details['rsa']['e'])) {
            return null;
        }

        $b64 = static fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        return ['kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => (string) $this->keyId(), 'n' => $b64($details['rsa']['n']), 'e' => $b64($details['rsa']['e'])];
    }

    /** Erzeugt die Schlüssel, wenn sie fehlen. @return bool true, wenn neu erzeugt */
    public function ensure(): bool
    {
        if ($this->exists()) {
            return false;
        }

        $this->generate();

        return true;
    }

    /** @throws DomainException */
    public function renew(User $by): void
    {
        $this->generate();
        AuditLog::record(AuditEvent::OauthKeysRenewed, $by);
    }

    private function generate(): void
    {
        // Zuerst direkt mit OpenSSL (läuft auch bei Aufrufen über den Browser, wo die Passport-Befehle nicht bekannt sind), sonst über den Befehl
        if (! $this->generateWithOpenSsl()) {
            Artisan::call('passport:keys', ['--force' => true]);
        }

        if (! $this->exists()) {
            throw new DomainException('Die Schlüssel konnten nicht erzeugt werden. Prüfen Sie, ob der Ordner storage beschreibbar ist.');
        }

        @chmod($this->privatePath(), 0600);
        @chmod($this->publicPath(), 0660);
    }

    /** Erzeugt das RSA-Schlüsselpaar ohne Artisan. @return bool false, wenn OpenSSL es nicht kann oder die Dateien nicht geschrieben werden konnten */
    private function generateWithOpenSsl(): bool
    {
        $key = @openssl_pkey_new(['private_key_bits' => app()->environment('testing') ? 2048 : 4096, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        if ($key === false || ! @openssl_pkey_export($key, $private)) {
            return false;
        }

        $details = openssl_pkey_get_details($key);

        if (! is_array($details) || ! isset($details['key'])) {
            return false;
        }

        $directory = dirname($this->privatePath());

        if (! is_dir($directory) || ! is_writable($directory)) {
            return false;
        }

        return @file_put_contents($this->privatePath(), $private, LOCK_EX) !== false && @file_put_contents($this->publicPath(), $details['key'], LOCK_EX) !== false;
    }
}
