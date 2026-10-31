<?php

namespace App\Modules\Partners\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Partners. Ein Konto mit der Rolle Schule, das zu genau einer Schule gehört (angelegt aus einer Ansprechperson). */
class PartnerUser extends Model
{
    protected $fillable = ['partner_id', 'user_id', 'contact_id', 'name'];

    /** @return BelongsTo<Partner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<PartnerContact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(PartnerContact::class, 'contact_id');
    }
}
