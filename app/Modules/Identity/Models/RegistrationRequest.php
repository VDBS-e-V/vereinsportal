<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\RegistrationRequestStatus;
use Illuminate\Database\Eloquent\Model;

class RegistrationRequest extends Model
{
    protected $fillable = [
        'public_id',
        'first_name',
        'last_name',
        'birth_date',
        'email',
        'password',
        'privacy_notice_version',
        'consented_at',
        'verification_recipient_email',
        'verification_version',
        'verification_expires_at',
        'expires_at',
        'status',
        'verification_sent_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'consented_at' => 'datetime',
            'verification_expires_at' => 'datetime',
            'expires_at' => 'datetime',
            'verification_version' => 'integer',
            'status' => RegistrationRequestStatus::class,
            'verification_sent_at' => 'datetime',
        ];
    }
}
