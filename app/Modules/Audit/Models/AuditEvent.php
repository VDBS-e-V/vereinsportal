<?php

namespace App\Modules\Audit\Models;

use App\Modules\Audit\Enums\AuditActorType;
use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'occurred_at',
        'event_key',
        'actor_type',
        'actor_user_id',
        'actor_context',
        'actor_anonymous_ref',
        'subject_type',
        'subject_id',
        'subject_anonymous_ref',
        'old_values',
        'new_values',
        'comment',
        'ip_address',
        'user_agent',
        'device_info',
        'retention_until',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'actor_type' => AuditActorType::class,
            'actor_user_id' => 'integer',
            'subject_id' => 'integer',
            'old_values' => 'array',
            'new_values' => 'array',
            'device_info' => 'array',
            'retention_until' => 'datetime',
        ];
    }
}