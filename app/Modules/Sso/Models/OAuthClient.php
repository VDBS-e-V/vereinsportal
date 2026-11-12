<?php

namespace App\Modules\Sso\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client;

/** Passport-Client, der die Zustimmungsseite überspringt, wenn die Anbindung das so eingestellt hat (z. B. Nextcloud des Vereins). */
class OAuthClient extends Client
{
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        $setting = OAuthClientSetting::query()->where('client_id', (string) $this->getKey())->first();

        return $setting !== null && ! $setting->show_consent;
    }
}
