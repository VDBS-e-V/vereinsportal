<?php

namespace App\Modules\Events\Models;

use App\Modules\Events\Enums\EventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Events. Eine Großveranstaltung für Schülerinnen und Schüler mehrerer Schulen mit Anmeldung, Anwesenheit und Teilnahmebestätigung. */
class Event extends Model
{
    /** Anmeldungen ohne Bestätigung der E-Mail-Adresse zählen so lange als Platz und verfallen dann. */
    public const PENDING_HOURS = 48;

    protected $attributes = ['status' => 'draft'];

    protected $fillable = ['slug', 'title', 'description', 'location', 'status', 'capacity', 'registration_deadline', 'created_by_user_id', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['status' => EventStatus::class, 'registration_deadline' => 'datetime', 'capacity' => 'integer'];
    }

    /** @return HasMany<EventSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class)->orderBy('starts_at')->orderBy('id');
    }

    /** @return HasMany<EventRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class)->orderBy('name')->orderBy('id');
    }

    public function confirmedCount(): int
    {
        return $this->registrations()->whereNotNull('verified_at')->count();
    }

    /** Belegte Plätze: Bestätigte und noch nicht verfallene unbestätigte Anmeldungen. */
    public function takenSpots(): int
    {
        return $this->registrations()->where(fn ($query) => $query->whereNotNull('verified_at')->orWhere('created_at', '>', now()->subHours(self::PENDING_HOURS)))->count();
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->takenSpots() >= $this->capacity;
    }

    public function spotsLeft(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->takenSpots());
    }

    public function deadlinePassed(): bool
    {
        return $this->registration_deadline !== null && $this->registration_deadline->isPast();
    }

    /** Anmeldung möglich: offen, Frist nicht abgelaufen, Platz frei. */
    public function acceptsRegistrations(): bool
    {
        return $this->status === EventStatus::Open && ! $this->deadlinePassed() && ! $this->isFull();
    }
}
