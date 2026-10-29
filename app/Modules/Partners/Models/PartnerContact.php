<?php

namespace App\Modules\Partners\Models;

use App\Modules\People\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Partners. Eine Ansprechperson einer Schule (frei erfasst, optional mit einer bekannten Person verknüpft). */
class PartnerContact extends Model
{
    public const MAX_PER_PARTNER = 5;

    protected $fillable = ['partner_id', 'person_id', 'name', 'role', 'email', 'phone', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    /** @return BelongsTo<Partner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
