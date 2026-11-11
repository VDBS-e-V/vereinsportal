<?php

namespace App\Modules\Board\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Modul: Board. Ein Anhang zu einer Vorstandssitzung (Protokoll als PDF, Vorlagen, Unterlagen), privat gespeichert, nie öffentlich verlinkt. */
class BoardMeetingDocument extends Model
{
    public const DISK = 'local';

    protected $fillable = ['board_meeting_id', 'title', 'original_name', 'path', 'mime', 'size', 'uploaded_by_user_id'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleting(function (BoardMeetingDocument $document): void {
            Storage::disk(self::DISK)->delete($document->path);
        });
    }

    /** @return BelongsTo<BoardMeeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(BoardMeeting::class, 'board_meeting_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function sizeLabel(): string
    {
        return $this->size >= 1048576 ? round($this->size / 1048576, 1).' MB' : max(1, (int) round($this->size / 1024)).' KB';
    }
}
