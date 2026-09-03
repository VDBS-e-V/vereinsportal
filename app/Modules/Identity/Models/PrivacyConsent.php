<?php

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyConsent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'person_id',
        'user_id',
        'context',
        'notice_version',
        'accepted',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted' => 'boolean',
            'accepted_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}