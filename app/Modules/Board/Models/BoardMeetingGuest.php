<?php

namespace App\Modules\Board\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Board. Ein externer Gast einer Vorstandssitzung. Gäste haben kein Portalkonto; sie erhalten nur die Einladung per E-Mail. */
class BoardMeetingGuest extends Model
{
    protected $fillable = ['board_meeting_id', 'name', 'email', 'organization', 'invited_at'];

    protected function casts(): array
    {
        return ['invited_at' => 'datetime'];
    }

    /** @return BelongsTo<BoardMeeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(BoardMeeting::class, 'board_meeting_id');
    }

    /** Name mit Organisation für Listen. */
    public function label(): string
    {
        return $this->name.(filled($this->organization) ? ' ('.$this->organization.')' : '');
    }
}
