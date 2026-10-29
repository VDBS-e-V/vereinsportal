<?php

namespace App\Modules\Onboarding\Models;

use App\Modules\Access\Models\User;
use App\Modules\Onboarding\Enums\TeamStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Onboarding. Die Einarbeitung eines Kontos mit der Rolle Teamende. */
class TeamOnboarding extends Model
{
    protected $fillable = ['user_id', 'started_at', 'started_by_user_id', 'completed_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<TeamOnboardingStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(TeamOnboardingStep::class, 'onboarding_id')->orderBy('id');
    }

    public function step(TeamStep $step): ?TeamOnboardingStep
    {
        return $this->steps->firstWhere('step', $step->value);
    }

    /** @return array{0: int, 1: int} erledigte und alle Schritte */
    public function progress(): array
    {
        return [$this->steps->whereNotNull('done_at')->count(), count(TeamStep::cases())];
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }
}
