<?php

namespace App\Modules\NavigationControl\Models;

use Illuminate\Database\Eloquent\Model;

/** Ein ausgeblendeter Bereich („area:<Bereich>“) oder eine ausgeblendete Seite („page:<Bereich>:<Seite>“) im Kopfmenü. */
class NavigationHidden extends Model
{
    protected $table = 'navigation_hidden';

    protected $fillable = ['key'];
}
