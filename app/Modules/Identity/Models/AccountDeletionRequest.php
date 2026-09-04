<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountDeletionRequest extends Model
{
    protected $fillable = [
        'public_id',
        'user_id',
        'anonymous_user_ref',
        'status',
        'requested_at',
        'confirmation_sent_at',
        'confirmed_at',
        'revoke_until',
        'withdrawn_at',
        'stopped_at',
        'stopped_by_user_id',
        'stop_reason_id',
        'stop_comment',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountDeletionRequestStatus::class,
            'requested_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'revoke_until' => 'datetime',
            'withdrawn_at' => 'datetime',
            'stopped_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stoppedByUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'stopped_by_user_id',
        );
    }

    public function stopReason(): BelongsTo
    {
        return $this->belongsTo(
            AccountDeletionStopReason::class,
            'stop_reason_id',
        );
    }
}
