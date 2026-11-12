<?php

namespace App\Modules\AccessRequests\Models;

use App\Modules\Access\Models\User;
use App\Modules\AccessRequests\Enums\AccessConnection;
use App\Modules\AccessRequests\Enums\AccessRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: AccessRequests. Ein Antrag auf Zugang zum Portal (öffentliches Formular), geprüft in der Geschäftsstelle. */
class AccessRequest extends Model
{
    protected $fillable = [
        'status', 'connection', 'organization', 'details', 'first_name', 'last_name', 'email', 'birth_date', 'phone',
        'consented_at', 'terms_version', 'decided_by_user_id', 'decided_at', 'decision_note', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccessRequestStatus::class,
            'connection' => AccessConnection::class,
            'birth_date' => 'date',
            'consented_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** @return HasMany<AccessRequestFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(AccessRequestFile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    /** @return BelongsTo<User, $this> das angelegte Konto nach der Annahme */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
