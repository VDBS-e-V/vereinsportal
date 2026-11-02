<?php

namespace App\Modules\Mailings\Models;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Mailings. Ein versendetes Schreiben (Betreff und Text mit Platzhaltern, Zahl der Empfänger), nie die Empfängerliste. */
class Mailing extends Model
{
    public const KIND_MEMBERS = 'members';

    public const KIND_TEAM = 'team';

    public const KIND_GENERAL = 'general';

    protected $fillable = ['kind', 'subject', 'body', 'own_text', 'seminar_id', 'audience', 'groups', 'recipients_count', 'failed_count', 'sent_by_user_id'];

    protected function casts(): array
    {
        return ['groups' => 'array'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
