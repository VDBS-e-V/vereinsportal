<?php

namespace App\Modules\Sso\Models;

use Illuminate\Database\Eloquent\Model;

/** Zustimmung einer Person zur Weitergabe ihrer Daten an ein angebundenes System. Gilt für die genannten Bereiche (Scopes), bis sie widerrufen wird. */
class OAuthConsent extends Model
{
    protected $table = 'oauth_consents';

    protected $fillable = ['user_id', 'client_id', 'scopes', 'granted_at'];

    protected function casts(): array
    {
        return ['scopes' => 'array', 'granted_at' => 'datetime'];
    }
}
