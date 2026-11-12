<?php

namespace App\Modules\Onboarding\Models;

use App\Modules\Access\Models\User;
use App\Modules\Onboarding\Enums\TeamStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Onboarding. Ein Schritt der Einarbeitung. Nur Datum, Notiz und Bestätigung werden gespeichert, keine Nachweise. */
class TeamOnboardingStep extends Model
{
    protected $fillable = ['onboarding_id', 'step', 'done_at', 'done_by_user_id', 'by_self', 'document_id', 'data', 'note'];

    protected function casts(): array
    {
        return ['done_at' => 'datetime', 'by_self' => 'boolean', 'data' => 'array'];
    }

    public function kind(): TeamStep
    {
        return TeamStep::from($this->step);
    }

    public function isDone(): bool
    {
        return $this->done_at !== null;
    }

    /** @return BelongsTo<TeamOnboarding, $this> */
    public function onboarding(): BelongsTo
    {
        return $this->belongsTo(TeamOnboarding::class, 'onboarding_id');
    }

    /** @return BelongsTo<User, $this> */
    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by_user_id');
    }

    /** @return BelongsTo<OnboardingDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(OnboardingDocument::class, 'document_id');
    }
}
