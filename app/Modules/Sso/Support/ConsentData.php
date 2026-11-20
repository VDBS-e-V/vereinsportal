<?php

namespace App\Modules\Sso\Support;

use App\Modules\AccessGovernance\Enums\RoleKey;
use Illuminate\Support\Str;

/** Die Daten, die an ein angebundenes System gehen, in lesbarer Form (Bezeichnung, Wert, Erklärung) für die Zustimmungsseite. */
final class ConsentData
{
    /**
     * @param  array<string, mixed>  $claims  Angaben laut OidcClaims::forUser
     * @return list<array{label: string, value: string, note: ?string}>
     */
    public static function rows(array $claims): array
    {
        $rows = [];

        $add = function (string $label, string $value, ?string $note = null) use (&$rows): void {
            $rows[] = ['label' => $label, 'value' => $value, 'note' => $note];
        };

        if (isset($claims['sub'])) {
            $add('Kennung (Pseudonym)', (string) $claims['sub'], 'Eine zufällige Kennung Ihres Kontos, nur für Anmeldungen über das Portal. Sie enthält weder Ihren Namen noch Ihre E-Mail-Adresse. Sie dient dem Dienst als Benutzername.');
        }

        foreach (['name' => 'Name', 'given_name' => 'Vorname', 'family_name' => 'Nachname'] as $key => $label) {
            if (isset($claims[$key]) && $claims[$key] !== '') {
                $add($label, (string) $claims[$key]);
            }
        }

        if (isset($claims['email'])) {
            $add('E-Mail-Adresse', (string) $claims['email']);
            $add('E-Mail-Adresse bestätigt', ($claims['email_verified'] ?? false) ? 'ja' : 'nein');
        }

        if (isset($claims['roles'])) {
            $labels = collect((array) $claims['roles'])->map(fn ($role): string => RoleKey::tryFrom((string) $role)?->label() ?? (string) $role);
            $add('Rollen im Verein', $labels->isEmpty() ? 'keine' : $labels->implode(', '));
        }

        if (isset($claims['groups'])) {
            $add('Gruppen im Verein', empty($claims['groups']) ? 'keine' : implode(', ', (array) $claims['groups']), 'Gruppen wie „vdbs-teamende“ ordnet der Dienst Ihrem Konto zu, zum Beispiel für gemeinsame Ordner oder Kurse.');
        }

        if (isset($claims['headline'])) {
            $add('Funktion', (string) $claims['headline']);
        }

        if (isset($claims['bio'])) {
            $add('Kurzvorstellung', Str::limit(trim(strip_tags((string) $claims['bio'])), 160), 'Aus Ihrem Profil, soweit Sie sie für angemeldete Personen freigegeben haben.');
        }

        if (isset($claims['picture'])) {
            $add('Profilbild', 'wird einmalig vom Portal abgerufen', 'Nur, wenn Sie das Foto in Ihrem Profil für angemeldete Personen freigegeben haben.');
        }

        return $rows;
    }
}
