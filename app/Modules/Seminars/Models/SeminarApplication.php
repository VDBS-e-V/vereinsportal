<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Bewerbung eines Teamenden-Kontos auf ein Seminar. */
class SeminarApplication extends Model
{
    protected $fillable = ['seminar_id', 'user_id', 'contact_id', 'guest_name', 'guest_phone', 'guest_birth_year', 'guest_competences', 'guest_experience', 'guest_consent_contact', 'confirmed_at', 'status', 'note', 'wants_lead', 'is_lead', 'internal_note', 'decided_by_user_id', 'decided_at'];

    protected function casts(): array
    {
        return ['status' => ApplicationStatus::class, 'decided_at' => 'datetime', 'wants_lead' => 'boolean', 'is_lead' => 'boolean', 'guest_competences' => 'array', 'guest_consent_contact' => 'boolean', 'confirmed_at' => 'datetime'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<\App\Modules\Contacts\Models\TeamContact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Contacts\Models\TeamContact::class, 'contact_id');
    }

    /** Bewerbung über den persönlichen Zugang (ohne Konto). */
    public function isContact(): bool
    {
        return $this->user_id === null && $this->contact_id !== null;
    }

    /** Zählt die Bewerbung? Konten ja; Kontakte erst, wenn die Adresse bestätigt ist. */
    public function isConfirmedApplicant(): bool
    {
        return $this->user_id !== null || $this->confirmed_at !== null;
    }

    /** Daten sind nach der Aufbewahrungsfrist entfernt. */
    public function isAnonymized(): bool
    {
        return $this->user_id === null && $this->contact_id === null;
    }

    public function applicantName(): string
    {
        if ($this->isAnonymized()) {
            return 'Anonymisiert';
        }

        if ($this->isContact()) {
            return $this->guest_name ?: ($this->contact?->displayName() ?? 'Unbekannt');
        }

        return $this->user?->person?->fullName() ?: (string) $this->user?->email;
    }

    public function applicantEmail(): ?string
    {
        return $this->isContact() ? $this->contact?->email : $this->user?->email;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
