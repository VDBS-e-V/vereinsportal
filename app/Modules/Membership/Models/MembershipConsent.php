<?php

namespace App\Modules\Membership\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\MembershipConsentSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipConsent extends Model
{
    protected $fillable = [
        'membership_id',
        'consent_key',
        'label',
        'version',
        'source',
        'granted_at',
        'revoked_at',
        'notes',
        'recorded_by_user_id',
        'revoked_by_user_id',
        'revocation_reason',
    ];

    protected function casts(): array
    {
        return [
            'source' => MembershipConsentSource::class,
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Membership, $this> */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
