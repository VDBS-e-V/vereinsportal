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
        Artisan::call('passport:keys', ['--force' => true]);

        if (! $this->exists()) {
            throw new DomainException('Die Schlüssel konnten nicht erzeugt werden. Prüfen Sie, ob der Ordner storage beschreibbar ist.');
        }

        @chmod($this->privatePath(), 0600);
        @chmod($this->publicPath(), 0660);
    }
}
