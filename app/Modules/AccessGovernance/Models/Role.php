<?php

namespace App\Modules\AccessGovernance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modul: Access-Governance.
 *
 * Rollen-Katalog. Die zehn Portal-Bereiche (Start, Konto, Vorstand,
 * Mitglieder, Teamende, Schulen, Koordination, Geschaeftsstelle,
 * Bildungsreferenten, Admins) sind KEINE eigene Tabelle, sondern eine
 * Konfiguration in der Anwendungsschicht, die jede Rolle auf sichtbare
 * Bereiche/Capabilities abbildet (Nachfolger von PortalAreaCatalog).
 */
class Role extends Model
{
    protected $fillable = [
        'key',
        'name',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /** @return HasMany<RoleAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }
}
