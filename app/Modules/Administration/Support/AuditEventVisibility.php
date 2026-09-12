<?php

namespace App\Modules\Administration\Support;

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\RoleKey;
use Illuminate\Database\Eloquent\Builder;

final class AuditEventVisibility
{
    /** @var list<string> */
    private const MEMBERSHIP_EVENT_KEYS = [
        AuditEventCatalog::MEMBERSHIP_CREATED,
        AuditEventCatalog::MEMBERSHIP_UPDATED,
        AuditEventCatalog::MEMBERSHIP_ENDED,
        AuditEventCatalog::MEMBERSHIP_DOCUMENT_UPLOADED,
        AuditEventCatalog::MEMBERSHIP_DOCUMENT_REPLACED,
        AuditEventCatalog::MEMBERSHIP_DOCUMENT_DOWNLOADED,
        AuditEventCatalog::MEMBERSHIP_CONSENT_RECORDED,
        AuditEventCatalog::MEMBERSHIP_CONSENT_REVOKED,
    ];

    /** @var list<string> */
    private const ROLE_EVENT_KEYS = [
        AuditEventCatalog::ROLE_AUTOMATIC_ASSIGNED,
        AuditEventCatalog::ROLE_MANUAL_ASSIGNED,
        AuditEventCatalog::ROLE_MANUAL_ENDED,
    ];

    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    public function constrain(
        Builder $query,
        bool $canReadMemberships,
    ): Builder {
        if ($canReadMemberships) {
            return $query;
        }

        return $query
            ->whereNotIn('event_key', self::MEMBERSHIP_EVENT_KEYS)
            ->where(function (Builder $query): void {
                $query
                    ->whereNotIn('event_key', self::ROLE_EVENT_KEYS)
                    ->orWhere(function (Builder $roleEventQuery): void {
                        $roleEventQuery
                            ->whereIn('event_key', self::ROLE_EVENT_KEYS)
                            ->where(function (Builder $newValuesQuery): void {
                                $newValuesQuery
                                    ->whereNull('new_values->role')
                                    ->orWhere(
                                        'new_values->role',
                                        '!=',
                                        RoleKey::Member->value,
                                    );
                            })
                            ->where(function (Builder $oldValuesQuery): void {
                                $oldValuesQuery
                                    ->whereNull('old_values->role')
                                    ->orWhere(
                                        'old_values->role',
                                        '!=',
                                        RoleKey::Member->value,
                                    );
                            });
                    });
            });
    }

    public function allows(
        AuditEvent $event,
        bool $canReadMemberships,
    ): bool {
        if ($canReadMemberships) {
            return true;
        }

        if (in_array($event->event_key, self::MEMBERSHIP_EVENT_KEYS, true)) {
            return false;
        }

        if (! in_array($event->event_key, self::ROLE_EVENT_KEYS, true)) {
            return true;
        }

        return data_get($event->new_values, 'role') !== RoleKey::Member->value
            && data_get($event->old_values, 'role') !== RoleKey::Member->value;
    }
}
