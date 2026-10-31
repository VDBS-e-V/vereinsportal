<?php

namespace App\Modules\Events\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Events. Eine Mitteilung der Koordination an die Angemeldeten einer Großveranstaltung (Betreff, Text, Empfängergruppe, Zahl der Empfänger). Ohne Empfängerliste. */
class EventMessage extends Model
{
    public const AUDIENCES = ['confirmed' => 'Angemeldete mit Platz', 'waitlist' => 'Wartende', 'both' => 'Angemeldete und Wartende'];

    protected $fillable = ['event_id', 'subject', 'body', 'audience', 'recipients_count', 'sent_by_user_id'];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
