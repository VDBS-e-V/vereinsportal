<?php

namespace App\Modules\Tickets\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eintrag im Verlauf eines Tickets: angelegt, zugewiesen, Status oder Priorität geändert. */
class TicketEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['ticket_id', 'user_id', 'type', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Text für den Verlauf, z. B. „Status: Offen → In Bearbeitung“. */
    public function describe(): string
    {
        $data = (array) $this->data;

        return match ($this->type) {
            'created' => 'Ticket angelegt',
            'assigned' => filled($data['to'] ?? null) ? 'Zugewiesen an '.$data['to'] : 'Zuweisung aufgehoben',
            'status' => 'Status: '.($data['from'] ?? '–').' → '.($data['to'] ?? '–'),
            'priority' => 'Priorität: '.($data['from'] ?? '–').' → '.($data['to'] ?? '–'),
            default => $this->type,
        };
    }
}
