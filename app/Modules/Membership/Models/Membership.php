<?php

namespace App\Modules\Membership\Models;

use App\Modules\Identity\Models\Person;
use App\Modules\Membership\Enums\MembershipStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membership extends Model
{
    protected $fillable = [
        'person_id',
        'starts_on',
        'ends_on',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return HasMany<MembershipDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(MembershipDocument::class);
    }

    /** @return HasMany<MembershipConsent, $this> */
    public function consents(): HasMany
    {
        return $this->hasMany(MembershipConsent::class);
    }

    public function status(?CarbonInterface $at = null): MembershipStatus
    {
        $referenceDate = ($at ?? now())->toDateString();

        if ($this->starts_on->toDateString() > $referenceDate) {
            return MembershipStatus::Planned;
        }

        if (
            $this->ends_on !== null
            && $this->ends_on->toDateString() < $referenceDate
        ) {
            return MembershipStatus::Ended;
        }

        return MembershipStatus::Active;
    }
}
