<?php

namespace App\Modules\Partners\Models;

use App\Modules\Access\Models\User;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\PartnerType;
use App\Modules\People\Models\Person;
use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modul: Partners.
 *
 * Generische Partnerorganisation ohne eigene Mitgliedschaft (aktuell
 * ausschliesslich Schulen). `attributes` traegt typ-spezifische
 * Zusatzfelder, ohne dass dafuer eine eigene Tabelle noetig wird.
 */
#[UseFactory(PartnerFactory::class)]
class Partner extends Model
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'active',
    ];

    protected $fillable = [
        'public_id',
        'type',
        'name',
        'legal_name',
        'street',
        'house_number',
        'postal_code',
        'city',
        'country_code',
        'contact_person_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'status',
        'attributes',
        'notes',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => PartnerType::class,
            'status' => PartnerStatus::class,
            'attributes' => 'array',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function contactPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'contact_person_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
