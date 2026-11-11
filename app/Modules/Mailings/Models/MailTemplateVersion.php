<?php

namespace App\Modules\Mailings\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Mailings. Ein früherer Stand einer Mailvorlage (wird beim Speichern der Vorlage abgelegt und lässt sich wiederherstellen). */
class MailTemplateVersion extends Model
{
    protected $fillable = ['mail_template_id', 'name', 'subject', 'body', 'saved_by_user_id'];

    /** @return BelongsTo<User, $this> */
    public function savedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saved_by_user_id');
    }
}
