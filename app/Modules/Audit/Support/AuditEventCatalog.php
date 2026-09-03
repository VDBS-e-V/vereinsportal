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

    public const ACCOUNT_REGISTRATION_DELETED_UNVERIFIED =
        'account.registration.deleted_unverified';

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