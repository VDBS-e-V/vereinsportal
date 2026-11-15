<?php

namespace App\Modules\Sso\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Services\Profiles;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Support\Html\RichText;
use Illuminate\Support\Facades\URL;

/**
 * Angaben, die ein angebundenes System über eine Person erfährt – nur nach Zustimmung zum jeweiligen Bereich (Scope).
 * Bewusst nicht dabei: Anschrift, Geburtsdatum, Telefonnummer.
 * Der Scope portal_profile gibt Profilbild, Funktion und Kurzvorstellung weiter, aber nur, was die Person für „Angemeldete Portalnutzer“ freigegeben hat.
 */
final class OidcClaims
{
    public function __construct(private readonly Profiles $profiles) {}

    public const SCOPES = [
        'openid' => 'Sie anmelden',
        'profile' => 'Ihren Namen sehen',
        'email' => 'Ihre E-Mail-Adresse sehen',
        'roles' => 'Ihre Rolle im Verein sehen',
        'groups' => 'Ihre Gruppen im Verein sehen',
        'portal_profile' => 'Ihr Profilbild, Ihre Funktion und Ihre Kurzvorstellung sehen (nur, was Sie für Angemeldete freigegeben haben)',
    ];

    /** Stabile Gruppennamen je Rolle; in Nextcloud werden ihnen Gruppenordner zugeordnet. */
    public const GROUPS = [
        'administration' => 'vdbs-administration',
        'board_member' => 'vdbs-vorstand',
        'administration_staff' => 'vdbs-geschaeftsstelle',
        'coordination' => 'vdbs-koordination',
        'education_coordination' => 'vdbs-bildungsreferenten',
        'team' => 'vdbs-teamende',
        'member' => 'vdbs-mitglieder',
        'school' => 'vdbs-schulen',
        'employee' => 'vdbs-mitarbeitende',
        'managing_director' => 'vdbs-geschaeftsfuehrung',
        'treasury' => 'vdbs-kasse',
    ];

    /** Darf dieses Konto sich über das Portal bei anderen Systemen anmelden? */
    public static function usable(User $user): bool
    {
        return $user->status === UserStatus::Active;
    }

    /**
     * @param  list<string>  $scopes
     * @return array<string, mixed>
     */
    public function forUser(User $user, array $scopes): array
    {
        $user->loadMissing('person');
        $claims = ['sub' => (string) $user->public_uuid];

        if (in_array('profile', $scopes, true)) {
            $person = $user->person;
            $name = $person ? $person->fullName() : '';
            $claims['name'] = $name !== '' ? $name : (string) $user->email;
            $claims['given_name'] = (string) ($person?->first_name ?? '');
            $claims['family_name'] = (string) ($person?->last_name ?? '');
            $claims['preferred_username'] = (string) $user->public_uuid;
        }

        if (in_array('email', $scopes, true)) {
            $claims['email'] = (string) $user->email;
            $claims['email_verified'] = $user->email_verified_at !== null;
        }

        if (in_array('roles', $scopes, true) || in_array('groups', $scopes, true)) {
            $roles = Capabilities::activeRoleKeys($user);

            if (in_array('roles', $scopes, true)) {
                $claims['roles'] = $roles;
            }

            if (in_array('groups', $scopes, true)) {
                $claims['groups'] = array_values(array_unique(array_filter(array_map(fn (string $role): ?string => self::GROUPS[$role] ?? null, $roles))));
            }
        }

        if (in_array('portal_profile', $scopes, true) && $this->profiles->allowedFor($user)) {
            // Es gilt die Sichtbarkeit „Angemeldete Portalnutzer“ aus dem Profil der Person.
            $shown = $this->profiles->preview($user, true);

            if (isset($shown['headline'])) {
                $claims['headline'] = $shown['headline'];
            }

            if (isset($shown['bio'])) {
                $claims['bio'] = RichText::render($shown['bio']);
            }

            if (isset($shown['photo'])) {
                // Das System lädt das Bild selbst herunter: Adresse mit Signatur, zehn Minuten gültig.
                $claims['picture'] = URL::temporarySignedRoute('oidc.photo', now()->addMinutes(10), ['user' => $user->id]);
            }
        }

        return $claims;
    }
}
