<?php

namespace App\Modules\MailBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Modul: MailBuilder. Ein hochgeladenes Bild für Mails (Logo, Bildblöcke); liegt öffentlich, damit Mailprogramme es laden können. */
class MailAsset extends Model
{
    public const DISK = 'public';

    protected $fillable = ['path', 'original_name', 'mime', 'alt_default', 'width', 'height', 'size', 'uploaded_by_user_id'];

    /** Vollständige Adresse, wie sie in der Mail stehen muss. */
    public function url(): string
    {
        return Storage::disk(self::DISK)->url($this->path);
    }
}
