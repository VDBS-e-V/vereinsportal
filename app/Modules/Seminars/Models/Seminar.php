<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Feedback\Models\FeedbackLink;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Services\SeminarNumbers;
use Database\Factories\SeminarFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Seminars. Ein Schulseminar (SV-Tage, SV-Coaching oder SV-Wahl) mit Terminen, Verlauf und Kostenvoranschlägen. */
#[UseFactory(SeminarFactory::class)]
class Seminar extends Model
{
    /** @use HasFactory<SeminarFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'request'];

    protected $fillable = ['title', 'classes_count', 'location_notes', 'cost_center', 'coordinator_user_id', 'type', 'partner_id', 'status', 'grade_level', 'expected_participants', 'team_needed', 'application_deadline', 'team_competences', 'deadline_notified_at', 'team_informed_at', 'team_informed_key', 'notes', 'created_by_user_id', 'updated_by_user_id'];

    /** Jedes Seminar bekommt beim Anlegen eine Nummer; eine vergebene Nummer lässt sich nicht mehr ändern. */
    protected static function booted(): void
    {
        static::creating(function (Seminar $seminar): void {
            if (blank($seminar->number)) {
                $seminar->number = app(SeminarNumbers::class)->next();
            }
        });

        static::updating(function (Seminar $seminar): void {
            if ($seminar->isDirty('number') && filled($seminar->getOriginal('number'))) {
                $seminar->number = $seminar->getOriginal('number');
            }
        });
    }

    protected function casts(): array
    {
        return ['type' => SeminarType::class, 'status' => SeminarStatus::class, 'application_deadline' => 'date', 'team_competences' => 'array', 'deadline_notified_at' => 'datetime', 'team_informed_at' => 'datetime'];
    }

    /** Der Stichtag für Interessenbekundungen ist vorbei (der Stichtag selbst zählt noch mit). */
    public function deadlinePassed(): bool
    {
        return $this->application_deadline !== null && $this->application_deadline->copy()->endOfDay()->isPast();
    }

    /** @return BelongsTo<Partner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /** @return HasMany<SeminarSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(SeminarSession::class)->orderBy('starts_at')->orderBy('id');
    }

    /** @return HasMany<SeminarEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(SeminarEvent::class)->orderByDesc('occurred_at')->orderByDesc('id');
    }

    /** @return HasMany<SeminarApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(SeminarApplication::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return HasMany<SeminarDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(SeminarDocument::class)->orderBy('kind')->orderByDesc('id');
    }

    /** @return HasMany<SeminarClosure, $this> */
    public function closures(): HasMany
    {
        return $this->hasMany(SeminarClosure::class)->orderByDesc('id');
    }

    /** @return HasMany<FeedbackLink, $this> */
    public function feedbackLinks(): HasMany
    {
        return $this->hasMany(FeedbackLink::class);
    }

    /** Der geltende Abschluss (nicht wieder geöffnet), sonst null. */
    public function currentClosure(): ?SeminarClosure
    {
        return $this->closures()->whereNull('reopened_at')->first();
    }

    /** @return HasMany<SeminarTeamInvite, $this> */
    public function teamInvites(): HasMany
    {
        return $this->hasMany(SeminarTeamInvite::class);
    }

    /** @return HasMany<SeminarQuote, $this> */
    public function quotes(): HasMany
    {
        return $this->hasMany(SeminarQuote::class)->orderByDesc('id');
    }

    /** @return BelongsTo<User, $this> */
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
