<?php

namespace App\Modules\Sso\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Support\Capabilities;

/**
 * Angaben, die ein angebundenes System über eine Person erfährt – nur nach Zustimmung zum jeweiligen Bereich (Scope).
 * Bewusst nicht dabei: Anschrift, Geburtsdatum, Telefonnummer.
 */
final class OidcClaims
{
    public const SCOPES = [
        'openid' => 'Sie anmelden',
        'profile' => 'Ihren Namen sehen',
        'email' => 'Ihre E-Mail-Adresse sehen',
        'roles' => 'Ihre Rolle im Verein sehen',
        'groups' => 'Ihre Gruppen im Verein sehen',
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

        return $claims;
    }
}
