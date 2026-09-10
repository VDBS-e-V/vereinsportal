<?php

namespace App\Modules\Identity\Models;

use App\Modules\Membership\Models\Membership;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Person extends Model
{
    protected $table = 'persons';

    protected $fillable = [
        'title',
        'first_name',
        'name_addition',
        'last_name',
        'birth_date',
        'email',
        'phone',
        'street',
        'house_number',
        'postal_code',
        'city',
        'country_code',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** @return HasMany<Membership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}
