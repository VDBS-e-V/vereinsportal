<?php

namespace App\Modules\Seminars\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Allgemeiner Bewerbungslink eines Seminars für Personen ohne persönlichen Zugang. Gespeichert wird nur die Prüfsumme des Links. */
class SeminarTeamInvite extends Model
{
    protected $fillable = ['seminar_id', 'token_hash', 'created_by_user_id', 'expires_at', 'revoked_at', 'uses'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @param  Builder<SeminarTeamInvite>  $query */
    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')->where(fn (Builder $inner) => $inner->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
