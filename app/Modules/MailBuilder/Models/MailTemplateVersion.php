<?php

namespace App\Modules\MailBuilder\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: MailBuilder. Ein früherer Stand einer Mailvorlage (wird beim Speichern der Vorlage abgelegt und lässt sich wiederherstellen). */
class MailTemplateVersion extends Model
{
    protected $fillable = ['mail_template_id', 'name', 'subject', 'body', 'saved_by_user_id', 'blocks', 'layout_id', 'preheader'];

    protected function casts(): array
    {
        return ['blocks' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function savedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saved_by_user_id');
    }
}
