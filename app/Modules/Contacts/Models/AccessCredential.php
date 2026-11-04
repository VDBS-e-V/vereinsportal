<?php

namespace App\Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Contacts. Persönlicher Zugang (Link und Code) einer Adresse. Gespeichert werden nur Prüfsummen, nie Link oder Code im Klartext. */
class AccessCredential extends Model
{
    protected $fillable = ['contact_id', 'token_hash', 'code_hash', 'code_hint', 'sent_at', 'last_used_at', 'revoked_at', 'expires_at', 'uses'];

    protected $hidden = ['token_hash', 'code_hash'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'last_used_at' => 'datetime', 'revoked_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    /** @return BelongsTo<TeamContact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(TeamContact::class, 'contact_id');
    }

    /** @param  Builder<AccessCredential>  $query */
    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')->where(fn (Builder $inner) => $inner->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
