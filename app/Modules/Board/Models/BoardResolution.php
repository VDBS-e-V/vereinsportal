<?php

namespace App\Modules\Board\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Board. Ein Beschluss des Vorstands aus einer Sitzung, mit fortlaufender Nummer je Jahr (B-2026-001). */
class BoardResolution extends Model
{
    public const OUTCOMES = ['accepted' => 'Angenommen', 'rejected' => 'Abgelehnt', 'postponed' => 'Vertagt'];

    protected $fillable = ['board_meeting_id', 'year', 'seq', 'title', 'text', 'outcome', 'votes_for', 'votes_against', 'votes_abstain', 'created_by'];

    public function number(): string
    {
        return sprintf('B-%d-%03d', $this->year, $this->seq);
    }

    public function outcomeLabel(): string
    {
        return self::OUTCOMES[$this->outcome] ?? $this->outcome;
    }

    /** Abstimmung als Text, zum Beispiel „3 Ja, 1 Nein, 0 Enthaltungen“; leer, wenn nichts eingetragen ist. */
    public function votesLabel(): string
    {
        if ($this->votes_for === null && $this->votes_against === null && $this->votes_abstain === null) {
            return '';
        }

        return (int) $this->votes_for.' Ja, '.(int) $this->votes_against.' Nein, '.(int) $this->votes_abstain.' Enthaltung'.((int) $this->votes_abstain === 1 ? '' : 'en');
    }

    /** @return BelongsTo<BoardMeeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(BoardMeeting::class, 'board_meeting_id');
    }

    /** @return HasMany<BoardTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(BoardTask::class, 'board_resolution_id');
    }
}
