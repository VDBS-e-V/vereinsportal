<?php

namespace App\Modules\Mailings\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: Mailings. Eine Mailvorlage der Geschäftsstelle (Betreff und Text mit Platzhaltern). */
class MailTemplate extends Model
{
    protected $fillable = ['key', 'subject', 'body', 'updated_by_user_id'];
}
