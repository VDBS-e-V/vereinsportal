<?php

namespace App\Modules\MailBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modul: MailBuilder. Eine Mailvorlage: Betreff und Blöcke mit Platzhaltern. Art „system“: die geänderte Fassung einer Systemmail (system_key);
 * Art „general“: frei benannte Vorlage.
 */
class MailTemplate extends Model
{
    public const KIND_SYSTEM = 'system';

    public const KIND_GENERAL = 'general';

    protected $fillable = ['key', 'kind', 'name', 'description', 'category', 'audience', 'subject', 'body', 'updated_by_user_id', 'system_key', 'layout_id', 'blocks', 'preheader', 'plain_text', 'status'];

    protected function casts(): array
    {
        return ['blocks' => 'array'];
    }

    /** @return HasMany<MailTemplateVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(MailTemplateVersion::class)->orderByDesc('id');
    }
}
