<?php

namespace App\Modules\Partners\Models;

use App\Modules\Access\Models\User;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\PartnerType;
use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** Interne Kennung: Bundesland-Kürzel und Schulnummer, z. B. BE/08K06 (null, solange eins von beiden fehlt). */
    public function internalId(): ?string
    {
        $attributes = $this->getAttribute('attributes') ?? [];

        return \App\Modules\Partners\Support\PartnerData::internalId($attributes['federal_state'] ?? null, $attributes['school_number'] ?? null);
    }

    /** @return HasMany<PartnerContact, $this> Hauptansprechperson zuerst */
    public function contacts(): HasMany
    {
        return $this->hasMany(PartnerContact::class)->orderByDesc('is_primary')->orderBy('name')->orderBy('id');
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
