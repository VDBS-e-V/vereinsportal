<?php

namespace App\Modules\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Ein Anhang zu einem Ticket (an einer Nachricht), privat gespeichert und nur über die Ticket-Seite abrufbar. */
class TicketAttachment extends Model
{
    public const DISK = 'local';

    protected $fillable = ['ticket_id', 'ticket_message_id', 'original_name', 'path', 'mime', 'size', 'uploaded_by_user_id'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleting(function (TicketAttachment $attachment): void {
            Storage::disk(self::DISK)->delete($attachment->path);
        });
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sizeLabel(): string
    {
        return $this->size >= 1048576 ? number_format($this->size / 1048576, 1, ',', '.').' MB' : max(1, (int) round($this->size / 1024)).' KB';
    }
}
