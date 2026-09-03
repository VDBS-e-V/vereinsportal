<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'person_id',
        'email',
        'password',
        'status',
        'session_version',
        'force_password_change_at',
        'last_login_at',
        'anonymized_at',
        'anonymized_ref',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'email_verified_at' => 'datetime',
            'force_password_change_at' => 'datetime',
            'last_login_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}