<?php

namespace App\Modules\Mailings\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Mailings. Eine gesperrte Adresse: Schreiben an Gruppen gehen nicht mehr dorthin (harter Fehler, Beschwerde oder manuell). Aktiv, bis sie freigegeben wird. */
class MailSuppression extends Model
{
    public const HARD_BOUNCE = 'hard_bounce';

    public const COMPLAINT = 'complaint';

    public const MANUAL = 'manual';

    public const REASONS = [self::HARD_BOUNCE => 'Dauerhafter Zustellfehler', self::COMPLAINT => 'Beschwerde', self::MANUAL => 'Manuell gesperrt'];

    protected $fillable = ['email', 'reason', 'source', 'note', 'created_by_user_id', 'released_at', 'released_by_user_id'];

    protected function casts(): array
    {
        return ['released_at' => 'datetime'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('released_at');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
