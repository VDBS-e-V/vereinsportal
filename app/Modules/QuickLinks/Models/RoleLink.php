<?php

namespace App\Modules\QuickLinks\Models;

use Illuminate\Database\Eloquent\Model;

/** Ein wichtiger Link einer Rolle für das Profilmenü. Je kleiner die Wichtigkeit, desto wichtiger (1 = am wichtigsten). */
class RoleLink extends Model
{
    public const PER_ROLE = 3;

    protected $fillable = ['role_key', 'label', 'url', 'priority'];

    protected function casts(): array
    {
        return ['priority' => 'integer'];
    }
}
