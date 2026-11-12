<?php

namespace App\Modules\Board\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Board. Eine Vorstandssitzung mit Tagesordnung, Anwesenheit und Protokoll. */
class BoardMeeting extends Model
{
    public const STATUSES = ['planned' => 'Geplant', 'held' => 'Stattgefunden', 'cancelled' => 'Abgesagt'];

    protected $fillable = ['title', 'starts_at', 'ends_at', 'location', 'online_link', 'status', 'agenda', 'minutes', 'minutes_approved_on', 'invited_at', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'minutes_approved_on' => 'date', 'invited_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Die Dateien der Anhänge müssen mit gelöscht werden; die Datenbank allein ließe sie liegen.
        static::deleting(function (BoardMeeting $meeting): void {
            $meeting->documents()->get()->each->delete();
        });
    }

    /** @return HasMany<BoardMeetingDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(BoardMeetingDocument::class, 'board_meeting_id')->orderBy('created_at');
    }

    /** @return HasMany<BoardResolution, $this> */
    public function resolutions(): HasMany
    {
        return $this->hasMany(BoardResolution::class, 'board_meeting_id')->orderBy('year')->orderBy('seq');
    }

    /** @return HasMany<BoardTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(BoardTask::class, 'board_meeting_id')->orderByRaw('done_at is not null')->orderBy('due_on');
    }

    /** @return HasMany<BoardMeetingGuest, $this> */
    public function guests(): HasMany
    {
        return $this->hasMany(BoardMeetingGuest::class, 'board_meeting_id')->orderBy('name');
    }

    /** Der Termin wurde nach der letzten Einladung noch geändert. */
    public function changedSinceInvitation(): bool
    {
        return $this->invited_at !== null && $this->updated_at !== null && $this->updated_at->gt($this->invited_at->copy()->addSeconds(5));
    }

    /** @return BelongsToMany<User, $this> */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_meeting_user', 'board_meeting_id', 'user_id')->withPivot('status');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function period(): string
    {
        $same = $this->starts_at->isSameDay($this->ends_at);

        return $this->starts_at->format('d.m.Y, H:i').($same ? '–'.$this->ends_at->format('H:i') : ' – '.$this->ends_at->format('d.m.Y, H:i')).' Uhr';
    }

    public function hasMinutes(): bool
    {
        return filled(trim(strip_tags((string) $this->minutes)));
    }

    /** Name für Listen: Person des Kontos, sonst die E-Mail-Adresse. */
    public static function attendeeName(User $user): string
    {
        return $user->person?->fullName() ?: $user->email;
    }
}
