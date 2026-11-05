<?php

namespace App\Modules\Sso\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: SSO. Einstellungen einer Anbindung (Passport-Client): erlaubte Rollen, Beschreibung, Zustimmungsseite. */
class OAuthClientSetting extends Model
{
    protected $table = 'oauth_client_settings';

    protected $fillable = ['client_id', 'allowed_roles', 'description', 'contact', 'show_consent', 'created_by_user_id'];

    protected function casts(): array
    {
        return ['allowed_roles' => 'array', 'show_consent' => 'boolean'];
    }
}
