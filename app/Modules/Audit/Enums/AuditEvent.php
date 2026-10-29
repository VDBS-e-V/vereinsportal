<?php

namespace App\Modules\Audit\Enums;

/** Ereignisse des Protokolls. Neue Ereignisse hier ergänzen: Wert, Bezeichnung, Gruppe und Gewicht an einer Stelle. */
enum AuditEvent: string
{
    case Login = 'auth.login';
    case LoginFailed = 'auth.login_failed';
    case TwoFactorFailed = 'auth.two_factor_failed';

    case PasswordChanged = 'account.password_changed';
    case PasswordSet = 'account.password_set';
    case EmailChanged = 'account.email_changed';
    case SessionsEnded = 'account.sessions_ended';
    case ProfileUpdated = 'account.profile_updated';

    case TwoFactorEnabled = 'two_factor.enabled';
    case TwoFactorDisabled = 'two_factor.disabled';
    case RecoveryCodesRenewed = 'two_factor.recovery_codes_renewed';

    case UserCreated = 'user.created';
    case UserInvited = 'user.invited';
    case UserDisabled = 'user.disabled';
    case UserEnabled = 'user.enabled';
    case UserTwoFactorReset = 'user.two_factor_reset';
    case UserPasswordLinkSent = 'user.password_link_sent';

    case RoleAssigned = 'role.assigned';
    case RoleEnded = 'role.ended';

    case PersonCreated = 'person.created';
    case PersonUpdated = 'person.updated';
    case PersonDeleted = 'person.deleted';

    case AuditExported = 'audit.exported';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Angemeldet',
            self::LoginFailed => 'Anmeldung fehlgeschlagen',
            self::TwoFactorFailed => 'Zwei-Faktor-Code falsch',
            self::PasswordChanged => 'Passwort geändert',
            self::PasswordSet => 'Passwort festgelegt oder zurückgesetzt',
            self::EmailChanged => 'E-Mail-Adresse geändert',
            self::SessionsEnded => 'Andere Sitzungen beendet',
            self::ProfileUpdated => 'Profil geändert',
            self::TwoFactorEnabled => 'Zwei-Faktor eingerichtet',
            self::TwoFactorDisabled => 'Zwei-Faktor entfernt',
            self::RecoveryCodesRenewed => 'Wiederherstellungscodes erneuert',
            self::UserCreated => 'Konto angelegt',
            self::UserInvited => 'Einladung gesendet',
            self::UserDisabled => 'Konto gesperrt',
            self::UserEnabled => 'Konto entsperrt',
            self::UserTwoFactorReset => 'Zwei-Faktor zurückgesetzt',
            self::UserPasswordLinkSent => 'Passwort-Link gesendet',
            self::RoleAssigned => 'Rolle vergeben',
            self::RoleEnded => 'Rolle beendet',
            self::PersonCreated => 'Person angelegt',
            self::PersonUpdated => 'Person geändert',
            self::PersonDeleted => 'Person gelöscht',
            self::AuditExported => 'Protokoll exportiert',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::Login, self::LoginFailed, self::TwoFactorFailed => 'auth',
            self::PasswordChanged, self::PasswordSet, self::EmailChanged, self::SessionsEnded, self::ProfileUpdated => 'account',
            self::TwoFactorEnabled, self::TwoFactorDisabled, self::RecoveryCodesRenewed => 'two_factor',
            self::UserCreated, self::UserInvited, self::UserDisabled, self::UserEnabled, self::UserTwoFactorReset, self::UserPasswordLinkSent => 'users',
            self::RoleAssigned, self::RoleEnded => 'roles',
            self::PersonCreated, self::PersonUpdated, self::PersonDeleted => 'people',
            self::AuditExported => 'audit',
        };
    }

    /** @return array<string, string> Gruppe => Bezeichnung */
    public static function categories(): array
    {
        return [
            'auth' => 'Anmeldung',
            'account' => 'Konto',
            'two_factor' => 'Zwei-Faktor',
            'users' => 'Nutzerverwaltung',
            'roles' => 'Rollen',
            'people' => 'Personen',
            'audit' => 'Protokoll',
        ];
    }

    /** Farbton für Badges: Warnendes und Sperrendes fällt auf. */
    public function tone(): string
    {
        return match ($this) {
            self::LoginFailed, self::TwoFactorFailed, self::PersonDeleted => 'error',
            self::UserDisabled, self::UserTwoFactorReset, self::TwoFactorDisabled, self::RoleEnded => 'warning',
            self::Login, self::UserEnabled, self::TwoFactorEnabled => 'success',
            default => 'neutral',
        };
    }

    /** @return list<self> */
    public static function inCategory(string $category): array
    {
        return array_values(array_filter(self::cases(), static fn (self $case): bool => $case->category() === $category));
    }
}
