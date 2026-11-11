<?php

namespace App\Modules\Mailings\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: Mailings. Zähler vorläufiger Zustellfehler (voller Postfach, Server nicht erreichbar) je Adresse. Ab der Grenze wird die Adresse gesperrt. */
class MailSoftBounce extends Model
{
    protected $fillable = ['email', 'count', 'last_at'];

    protected function casts(): array
    {
        return ['last_at' => 'datetime'];
    }
}
