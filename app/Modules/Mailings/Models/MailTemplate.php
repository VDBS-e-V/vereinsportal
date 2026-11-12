<?php

namespace App\Modules\Mailings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modul: Mailings. Eine Mailvorlage (Betreff und Text mit Platzhaltern). Art „system“: der feste Rahmen für Mitteilungen an Teamende zu Seminaren.
 * Art „general“: Vorlagen für freie Schreiben an Gruppen, mit Name und Beschreibung.
 */
class MailTemplate extends Model
{
    public const KIND_SYSTEM = 'system';

    public const KIND_GENERAL = 'general';

    protected $fillable = ['key', 'kind', 'name', 'description', 'category', 'audience', 'subject', 'body', 'updated_by_user_id'];

    /** @return HasMany<MailTemplateVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(MailTemplateVersion::class)->orderByDesc('id');
    }
}
