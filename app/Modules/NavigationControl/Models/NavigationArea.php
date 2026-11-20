<?php

namespace App\Modules\NavigationControl\Models;

use Illuminate\Database\Eloquent\Model;

/** Einstellungen eines Bereichs im Kopfmenü: Reihenfolge, Anzeigename, Sichtbarkeit (Kopfzeile, Listen), erlaubte Rollen. */
class NavigationArea extends Model
{
    protected $fillable = ['area', 'sort', 'label', 'show_header', 'show_lists', 'roles'];

    protected function casts(): array
    {
        return ['show_header' => 'boolean', 'show_lists' => 'boolean', 'roles' => 'array'];
    }
}
