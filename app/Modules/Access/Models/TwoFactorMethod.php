<?php

namespace App\Modules\Access\Models;

use App\Modules\Access\Enums\TwoFactorMethodType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TwoFactorMethod extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'secret',
        'confirmed_at',
        'disabled_at',
    ];

    protected $hidden = [
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'type' => TwoFactorMethodType::class,

            // Laravel verschluesselt das TOTP-Secret transparent mit APP_KEY.
            'secret' => 'encrypted',

            'confirmed_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
