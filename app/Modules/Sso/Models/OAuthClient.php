<?php

namespace App\Modules\Sso\Models;

use App\Modules\Sso\Services\OAuthConsents;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client;

/**
 * Passport-Client. Die Zustimmungsseite entfällt, wenn die Anbindung sie abgeschaltet hat (Systeme des Vereins) oder die Person
 * den verlangten Bereichen schon zugestimmt hat (die Seite kommt nur bei der ersten Anmeldung in einem System).
 */
class OAuthClient extends Client
{
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        $setting = OAuthClientSetting::query()->where('client_id', (string) $this->getKey())->first();

        if ($setting !== null && ! $setting->show_consent) {
            return true;
        }

        return app(OAuthConsents::class)->covers($user, (string) $this->getKey(), array_map('strval', $scopes));
    }
}
