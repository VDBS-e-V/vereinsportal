<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\RoleAssignmentSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'role_id',
        'source',
        'source_type',
        'source_id',
        'starts_at',
        'ends_at',
        'granted_by_user_id',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'source' => RoleAssignmentSource::class,
            'source_id' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'granted_by_user_id',
        );
    }
}