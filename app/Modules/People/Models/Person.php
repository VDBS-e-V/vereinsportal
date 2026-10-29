<?php

namespace App\Modules\People\Models;

use App\Modules\Access\Models\User;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modul: People.
 *
 * Stammdaten echter Personen, unabhaengig vom Login-Zustand. Eine Person
 * kann ohne Benutzerkonto existieren (users.person_id ist nullable).
 *
 * @property-read User|null $user
 */
#[UseFactory(PersonFactory::class)]
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

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

    /** @return HasMany<\App\Modules\Membership\Models\Membership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(\App\Modules\Membership\Models\Membership::class);
    }

    public function fullName(): string
    {
        $parts = array_filter([
            $this->title,
            $this->first_name,
            $this->name_addition,
            $this->last_name,
        ], static fn (?string $part): bool => $part !== null && $part !== '');

        return implode(' ', $parts);
    }
}
