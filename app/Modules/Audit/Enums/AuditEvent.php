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
    case AccountDeletionRequested = 'account.deletion_requested';
    case AccountDeletionCancelled = 'account.deletion_cancelled';
    case AccountAnonymized = 'account.anonymized';

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

    case PartnerCreated = 'partner.created';
    case PartnerUpdated = 'partner.updated';
    case PartnerDeleted = 'partner.deleted';
    case PartnersImported = 'partner.imported';

    case SeminarCreated = 'seminar.created';
    case SeminarUpdated = 'seminar.updated';
    case SeminarDeleted = 'seminar.deleted';
    case SeminarStatusChanged = 'seminar.status_changed';
    case SeminarQuoteCreated = 'seminar.quote_created';
    case SeminarQuoteDeleted = 'seminar.quote_deleted';
    case SeminarPricesChanged = 'seminar.prices_changed';

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
            self::AccountDeletionRequested => 'Kontolöschung beantragt',
            self::AccountDeletionCancelled => 'Kontolöschung abgebrochen',
            self::AccountAnonymized => 'Konto gelöscht (anonymisiert)',
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
            self::PartnerCreated => 'Schule angelegt',
            self::PartnerUpdated => 'Schule geändert',
            self::PartnerDeleted => 'Schule gelöscht',
            self::PartnersImported => 'Schulen importiert',
            self::SeminarCreated => 'Seminar angelegt',
            self::SeminarUpdated => 'Seminar geändert',
            self::SeminarDeleted => 'Seminar gelöscht',
            self::SeminarStatusChanged => 'Seminarstatus geändert',
            self::SeminarQuoteCreated => 'Kostenvoranschlag erstellt',
            self::SeminarQuoteDeleted => 'Kostenvoranschlag gelöscht',
            self::SeminarPricesChanged => 'Preisliste der Seminare geändert',
            self::AuditExported => 'Protokoll exportiert',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::Login, self::LoginFailed, self::TwoFactorFailed => 'auth',
            self::PasswordChanged, self::PasswordSet, self::EmailChanged, self::SessionsEnded, self::ProfileUpdated, self::AccountDeletionRequested, self::AccountDeletionCancelled, self::AccountAnonymized => 'account',
            self::TwoFactorEnabled, self::TwoFactorDisabled, self::RecoveryCodesRenewed => 'two_factor',
            self::UserCreated, self::UserInvited, self::UserDisabled, self::UserEnabled, self::UserTwoFactorReset, self::UserPasswordLinkSent => 'users',
            self::RoleAssigned, self::RoleEnded => 'roles',
            self::PersonCreated, self::PersonUpdated, self::PersonDeleted => 'people',
            self::PartnerCreated, self::PartnerUpdated, self::PartnerDeleted, self::PartnersImported => 'partners',
            self::SeminarCreated, self::SeminarUpdated, self::SeminarDeleted, self::SeminarStatusChanged, self::SeminarQuoteCreated, self::SeminarQuoteDeleted, self::SeminarPricesChanged => 'seminars',
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
            'partners' => 'Schulen',
            'seminars' => 'Seminare',
            'audit' => 'Protokoll',
        ];
    }

    /** Farbton für Badges: Warnendes und Sperrendes fällt auf. */
    public function tone(): string
    {
        return match ($this) {
            self::LoginFailed, self::TwoFactorFailed, self::PersonDeleted, self::PartnerDeleted, self::SeminarDeleted, self::SeminarQuoteDeleted, self::AccountAnonymized => 'error',
            self::UserDisabled, self::UserTwoFactorReset, self::TwoFactorDisabled, self::RoleEnded, self::AccountDeletionRequested => 'warning',
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
