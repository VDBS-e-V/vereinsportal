<?php

namespace App\Modules\Audit\Support;

use InvalidArgumentException;

final class AuditEventCatalog
{
    public const AUTH_LOGIN_SUCCEEDED = 'auth.login.succeeded';

    public const AUTH_LOGIN_FAILED = 'auth.login.failed';

    public const AUTH_LOGIN_LOCKED = 'auth.login.locked';

    public const AUTH_LOGOUT = 'auth.logout';

    public const AUTH_SESSIONS_INVALIDATED = 'auth.sessions.invalidated';

    public const AUTH_PASSWORD_CHANGED = 'auth.password.changed';

    public const AUTH_PASSWORD_RESET_REQUESTED =
        'auth.password_reset.requested';

    public const AUTH_PASSWORD_RESET_COMPLETED =
        'auth.password_reset.completed';

    public const AUTH_EMAIL_VERIFIED = 'auth.email_verified';

    public const ACCOUNT_REGISTERED = 'account.registered';

    public const ROLE_AUTOMATIC_ASSIGNED = 'role.automatic_assigned';

    public const EMAIL_TEMPLATE_PUBLISHED = 'email_template.published';

    public const EMAIL_TEMPLATE_ACTIVATED = 'email_template.activated';

    public const EMAIL_TEMPLATE_DEACTIVATED = 'email_template.deactivated';

    public const AUTH_VERIFICATION_RESENT = 'auth.verification.resent';

    public const ACCOUNT_REGISTRATION_DELETED_UNVERIFIED = 'account.registration.deleted_unverified';

    public const PERSON_UPDATED = 'person.updated';

    public const AUTH_EMAIL_CHANGE_REQUESTED = 'auth.email_change.requested';

    public const AUTH_EMAIL_CHANGE_SUPERSEDED = 'auth.email_change.superseded';

    public const AUTH_EMAIL_CHANGE_COMPLETED = 'auth.email_change.completed';

    public const AUTH_2FA_ENABLED = 'auth.2fa.enabled';

    public const AUTH_2FA_DISABLED = 'auth.2fa.disabled';

    public const AUTH_2FA_CHALLENGE_FAILED = 'auth.2fa.challenge.failed';

    public const AUTH_2FA_RECOVERY_CODE_USED = 'auth.2fa.recovery_code.used';

    public const AUTH_2FA_RECOVERY_CODES_REGENERATED = 'auth.2fa.recovery_codes.regenerated';

    public const AUTH_2FA_RECOVERY_COMPLETED = 'auth.2fa.recovery.completed';

    public const ACCOUNT_DELETION_REQUESTED = 'account.deletion.requested';

    public const ACCOUNT_DELETION_CONFIRMED = 'account.deletion.confirmed';

    /**
     * @var array<string, list<string>>
     */
    private const VALUE_FIELDS = [
        self::AUTH_LOGIN_SUCCEEDED => [
            'method',
            'remember_me',
        ],

        self::AUTH_LOGIN_FAILED => [
            'reason',
            'login_id',
        ],

        self::AUTH_LOGIN_LOCKED => [
            'scope',
            'locked_until',
        ],

        self::AUTH_LOGOUT => [
            'scope',
        ],

        self::AUTH_SESSIONS_INVALIDATED => [
            'reason',
        ],

        self::AUTH_PASSWORD_CHANGED => [],

        self::AUTH_PASSWORD_RESET_REQUESTED => [],

        self::AUTH_PASSWORD_RESET_COMPLETED => [],

        self::AUTH_EMAIL_VERIFIED => [
            'verified_at',
        ],

        self::ACCOUNT_REGISTERED => [
            'linkage_type',
        ],

        self::ROLE_AUTOMATIC_ASSIGNED => [
            'role',
            'source',
        ],

        self::EMAIL_TEMPLATE_PUBLISHED => [
            'version',
            'key',
        ],

        self::EMAIL_TEMPLATE_ACTIVATED => [
            'status',
        ],

        self::EMAIL_TEMPLATE_DEACTIVATED => [
            'status',
        ],

        self::AUTH_VERIFICATION_RESENT => [],

        self::ACCOUNT_REGISTRATION_DELETED_UNVERIFIED => [
            'reason',
        ],
        self::PERSON_UPDATED => [
            'title',
            'first_name',
            'name_addition',
            'last_name',
            'birth_date',
            'phone',
            'street',
            'house_number',
            'postal_code',
            'city',
            'country_code',
        ],

        self::AUTH_EMAIL_CHANGE_REQUESTED => [
            'old_email',
            'new_email',
        ],

        self::AUTH_EMAIL_CHANGE_SUPERSEDED => [
            'new_email',
        ],

        self::AUTH_EMAIL_CHANGE_COMPLETED => [
            'old_email',
            'new_email',
        ],
        self::AUTH_2FA_ENABLED => [
            'method',
        ],

        self::AUTH_2FA_DISABLED => [
            'method',
        ],

        self::AUTH_2FA_CHALLENGE_FAILED => [
            'method',
        ],

        self::AUTH_2FA_RECOVERY_CODE_USED => [],

        self::AUTH_2FA_RECOVERY_CODES_REGENERATED => [
            'count',
        ],

        self::AUTH_2FA_RECOVERY_COMPLETED => [
            'recovery_type',
        ],
        self::ACCOUNT_DELETION_REQUESTED => [
            'requested_at',
        ],
        self::ACCOUNT_DELETION_CONFIRMED => [
            'revoke_until',
        ],
    ];

    public static function filterValues(
        string $eventKey,
        ?array $values,
    ): ?array {
        if (! array_key_exists($eventKey, self::VALUE_FIELDS)) {
            throw new InvalidArgumentException(
                "Audit event [{$eventKey}] has no configured value whitelist."
            );
        }

        if ($values === null) {
            return null;
        }

        $allowedFields = array_flip(
            self::VALUE_FIELDS[$eventKey]
        );

        $filtered = array_intersect_key(
            $values,
            $allowedFields,
        );

        return $filtered === []
            ? null
            : $filtered;
    }
}
