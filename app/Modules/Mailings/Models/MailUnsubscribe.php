<?php

namespace App\Modules\Mailings\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: Mailings. Eine Abmeldung einer E-Mail-Adresse von einer Art von Mitteilungen (Thema). */
class MailUnsubscribe extends Model
{
    protected $fillable = ['email', 'topic'];
}
