<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\EmailChangeRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmailChangeRequest extends Model
{
    protected $fillable = [
        'public_id',
        'user_id',
        'old_email',
        'new_email',
        'status',
        'expires_at',
        'verification_sent_at',
        'confirmed_at',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailChangeRequestStatus::class,
            'expires_at' => 'datetime',
            'verification_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}