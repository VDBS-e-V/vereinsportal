<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Weiterführende Links für Fehlerseiten („Das könnte Ihnen helfen“).
 *
 * Jede Fehlerseite bietet neben ihren Buttons mehrere sinnvolle Wege an.
 * Die Liste ist bewusst großzügig; der Baustein x-vdbs.error-page entfernt
 * Ziele, die schon als Button vorkommen, und zeigt höchstens vier Links.
 *
 * Ab Status 500 (auch 503/Wartungsmodus) werden weder Sitzung noch
 * Anmeldung abgefragt, es gibt dann nur Links für Gäste.
 */
final class ErrorPageLinks
{
    /**
     * @return list<array{label: string, url: string}>
     */
    public static function for(string|int $code): array
    {
        $serverError = (int) $code >= 500;
        $signedIn = ! $serverError && self::signedIn();

        $links = [
            ['label' => 'Zur Startseite', 'url' => url('/')],
        ];

        if (! $serverError && ($previous = self::previousPage()) !== null) {
            $links[] = ['label' => 'Zurück zur vorherigen Seite', 'url' => $previous];
        }

        $routes = $signedIn
            ? [
                'Mein Konto' => 'my.account',
                'Mein Profil' => 'my.account.profile',
            ]
            : [
                'Anmelden' => 'my.login',
                'Konto registrieren' => 'my.registration.create',
                'Passwort vergessen' => 'my.password.request',
            ];

        foreach ($routes as $label => $name) {
            if (Route::has($name)) {
                $links[] = ['label' => $label, 'url' => route($name)];
            }
        }

        return $links;
    }

    private static function signedIn(): bool
    {
        try {
            return auth()->check();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Die zuvor besuchte Seite, aber nur wenn sie auf demselben Host liegt
     * und nicht die aktuelle Seite ist.
     */
    private static function previousPage(): ?string
    {
        try {
            $previous = url()->previous();
            $host = parse_url($previous, PHP_URL_HOST);

            if (
                $host === null
                || $host !== request()->getHost()
                || $previous === request()->fullUrl()
            ) {
                return null;
            }

            return $previous;
        } catch (Throwable) {
            return null;
        }
    }
}
