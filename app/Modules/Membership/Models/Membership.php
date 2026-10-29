<?php

namespace App\Modules\Membership\Models;

use App\Modules\Access\Models\User;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\People\Models\Person;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modul: Membership. Eine Mitgliedschaft einer Person (Antrag, Aufnahme, Austritt).
 *
 * @property MembershipStatus $status
 */
#[UseFactory(MembershipFactory::class)]
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory;

    protected $fillable = [
        'person_id',
        'first_name',
        'last_name',
        'birth_date',
        'email',
        'source',
        'registration_token_hash',
        'registration_sent_at',
        'registration_expires_at',
        'linked_at',
        'board_notified_at',
        'status',
        'requested_on',
        'starts_on',
        'ends_on',
        'decided_at',
        'decided_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
            'birth_date' => 'date',
            'registration_sent_at' => 'datetime',
            'registration_expires_at' => 'datetime',
            'linked_at' => 'datetime',
            'board_notified_at' => 'datetime',
            'requested_on' => 'date',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return HasMany<MembershipDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(MembershipDocument::class)->orderByDesc('uploaded_at')->orderByDesc('id');
    }

    /** @return HasMany<MembershipConsent, $this> */
    public function consents(): HasMany
    {
        return $this->hasMany(MembershipConsent::class)->orderBy('id');
    }

    /** Name der Person, sonst der an der Mitgliedschaft erfasste Name (Mitgliedschaft ohne digitale Person). */
    public function displayName(): string
    {
        return $this->person?->fullName() ?: trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    /** E-Mail-Adresse der Person, sonst die an der Mitgliedschaft hinterlegte. */
    public function contactEmail(): ?string
    {
        return $this->person?->email ?? $this->email;
    }

    /** Mitgliedschaft ohne digitale Person (manuell erfasst, noch nicht per Link registriert). */
    public function hasNoPerson(): bool
    {
        return $this->person_id === null;
    }

    /** Ein Registrierungslink ist gesendet und noch gültig. */
    public function registrationLinkActive(): bool
    {
        return $this->registration_token_hash !== null && $this->registration_expires_at !== null && $this->registration_expires_at->isFuture();
    }

    /** @return BelongsTo<User, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    /** @return HasMany<MembershipEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(MembershipEvent::class)->orderByDesc('occurred_at')->orderByDesc('id');
    }

    /** Status unter Berücksichtigung des Austrittsdatums: Aktiv mit erreichtem Austrittsdatum gilt als beendet. */
    public function effectiveStatus(): MembershipStatus
    {
        if ($this->status === MembershipStatus::Active && $this->ends_on !== null && $this->ends_on->lt(today())) {
            return MembershipStatus::Ended;
        }

        return $this->status;
    }

    /** Beantragt oder noch laufend (Aktiv ohne Austritt oder mit Austritt ab heute). Der Austritt gilt bis einschließlich seines Datums. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where(fn (Builder $open) => $open
            ->where('status', MembershipStatus::Requested->value)
            ->orWhere(fn (Builder $active) => $active
                ->where('status', MembershipStatus::Active->value)
                ->where(fn (Builder $end) => $end->whereNull('ends_on')->orWhere('ends_on', '>=', today()))));
    }

    /** Aktiv, aber mit eingetragenem Austritt (letzter Tag der Mitgliedschaft) heute oder später. */
    public function isEnding(): bool
    {
        return $this->effectiveStatus() === MembershipStatus::Active && $this->ends_on !== null;
    }

    public function isOpen(): bool
    {
        return $this->effectiveStatus()->isOpen();
    }
}
