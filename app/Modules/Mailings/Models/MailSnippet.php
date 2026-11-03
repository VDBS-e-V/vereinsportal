<?php

namespace App\Modules\Mailings\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: Mailings. Ein Textbaustein (Name und Text mit Platzhaltern), den man beim Schreiben in den Text einfügt. Das Einfügen kopiert den Text. */
class MailSnippet extends Model
{
    protected $fillable = ['name', 'body', 'updated_by_user_id'];
}
