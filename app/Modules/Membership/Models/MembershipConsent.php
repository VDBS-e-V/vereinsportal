<?php

namespace App\Modules\Membership\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Modul: Membership. Nachweis einer Einwilligung (Satzung oder Datenschutzhinweise) mit Fassung und Zeitpunkt.
 * Nachweise werden nur angelegt, nie geändert.
 */
class MembershipConsent extends Model
{
    public const TYPES = [
        'statute' => 'Satzung anerkannt',
        'privacy' => 'Datenschutzhinweise zur Kenntnis genommen',
    ];

    public $timestamps = false;

    protected $fillable = ['membership_id', 'type', 'version', 'accepted_at'];

    protected static function booted(): void
    {
        static::updating(static fn () => throw new LogicException('Einwilligungsnachweise sind unveränderlich.'));
    }

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function label(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** @return BelongsTo<Membership, $this> */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }
}
