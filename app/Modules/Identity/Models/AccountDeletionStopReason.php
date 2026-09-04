<?php

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AccountDeletionStopReason extends Model
{
    protected $fillable = [
        'key',
        'label',
        'requires_comment',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_comment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function deletionRequests(): HasMany
    {
        return $this->hasMany(
            AccountDeletionRequest::class,
            'stop_reason_id',
        );
    }
}
