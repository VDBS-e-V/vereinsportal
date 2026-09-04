<?php

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
