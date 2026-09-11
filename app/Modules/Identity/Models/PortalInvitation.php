<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\PortalInvitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalInvitation extends Model
{
    protected $fillable = [
        'public_id',
        'person_id',
        'email',
        'token_hash',
        'token_version',
        'expires_at',
        'sent_at',
        'accepted_at',
        'revoked_at',
        'created_by_user_id',
        'revoked_by_user_id',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function status(): PortalInvitationStatus
    {
        if ($this->accepted_at !== null) {
            return PortalInvitationStatus::Accepted;
        }

        if ($this->revoked_at !== null) {
            return PortalInvitationStatus::Revoked;
        }

        if ($this->expires_at->isPast()) {
            return PortalInvitationStatus::Expired;
        }

        return PortalInvitationStatus::Open;
    }

    public function isOpen(): bool
    {
        return $this->status() === PortalInvitationStatus::Open;
    }
}
