<?php

namespace App\Modules\Mailings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Modul: Mailings. Ein Anhang eines Schreibens im privaten Speicher. Die Datei wird mit dem Datensatz gelöscht. */
class MailingAttachment extends Model
{
    public const DISK = 'local';

    protected $fillable = ['mailing_id', 'original_name', 'path', 'mime', 'size'];

    protected static function booted(): void
    {
        static::deleting(function (MailingAttachment $attachment): void {
            Storage::disk(self::DISK)->delete($attachment->path);
        });
    }

    /** @return BelongsTo<Mailing, $this> */
    public function mailing(): BelongsTo
    {
        return $this->belongsTo(Mailing::class);
    }
}
