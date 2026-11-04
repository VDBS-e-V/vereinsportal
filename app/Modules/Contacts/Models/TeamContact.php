<?php

namespace App\Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modul: Contacts. Eine Adresse der importierten Mailingliste (Einwilligung liegt vor). Name, Telefon und Anschrift stehen nur da,
 * wenn die Person sie für die Zukunft speichern ließ oder die Geschäftsstelle sie eingetragen hat.
 */
class TeamContact extends Model
{
    protected $fillable = ['email', 'first_name', 'last_name', 'phone', 'street', 'house_number', 'postal_code', 'city', 'competences', 'profile_saved', 'import_batch_id', 'consent_confirmed_at', 'person_id'];

    protected function casts(): array
    {
        return ['competences' => 'array', 'profile_saved' => 'boolean', 'consent_confirmed_at' => 'datetime'];
    }

    /** @return HasMany<AccessCredential, $this> */
    public function credentials(): HasMany
    {
        return $this->hasMany(AccessCredential::class, 'contact_id')->orderByDesc('id');
    }

    public function displayName(): string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $name !== '' ? $name : $this->email;
    }
}
