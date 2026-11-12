<?php

namespace App\Modules\Membership\Models;

use App\Modules\Access\Models\User;
use App\Modules\Membership\Enums\MembershipEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modul: Membership. Ein Eintrag im Verlauf einer Mitgliedschaft (nur anlegen). Der Verlauf gehört zur Mitgliedschaft und
 * ist bewusst getrennt vom allgemeinen Protokoll, damit Mitgliedschaftsdaten nur im Vorstandsbereich erscheinen.
 */
class MembershipEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['membership_id', 'type', 'occurred_at', 'actor_user_id', 'actor_label', 'comment', 'data'];

    protected function casts(): array
    {
        return [
            'type' => MembershipEventType::class,
            'occurred_at' => 'datetime',
            'data' => 'array',
        ];
    }

    /** @return BelongsTo<Membership, $this> */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
