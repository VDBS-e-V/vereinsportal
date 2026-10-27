<?php

namespace App\Modules\Sso;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

/**
 * Modul: SSO.
 *
 * Portal als OIDC/OAuth2-Identity-Provider fuer externe Systeme
 * (Nextcloud, Moodle, Umfragetools) - fuer alle eingeloggten Nutzer,
 * laut Konzept selbst gehostet mit Laravel Passport statt separater
 * IdP-Software.
 *
 * Grant-Type: Authorization Code + PKCE, kein Password-Grant (seit
 * Passport 11 ohnehin entfernt).
 *
 * In dieser Sandbox nicht ausfuehrbar (kein vendor/, PHP 8.3 statt der
 * geforderten 8.4.1). In einer echten Umgebung muessen vor dem ersten
 * Einsatz noch laufen:
 *   php artisan migrate            (legt oauth_* Tabellen an)
 *   php artisan passport:keys      (RSA-Schluesselpaar)
 *   php artisan passport:client --name="Nextcloud" --redirect_uri=...
 *   (je ein weiterer Client fuer Moodle und das Umfragetool)
 */
class SsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Passport 11+ hat den Password-Grant komplett entfernt - es gibt
        // nichts mehr zu deaktivieren, nur Authorization-Code (+ PKCE)
        // steht zur Verfuegung, was der Konzept-Entscheidung entspricht.

        // Access-Tokens laufen nach 1 Stunde ab, Refresh-Tokens nach
        // 30 Tagen - externe Systeme erneuern transparent im
        // Hintergrund ueber den Refresh-Token.
        Passport::tokensExpireIn(now()->addHour());
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
