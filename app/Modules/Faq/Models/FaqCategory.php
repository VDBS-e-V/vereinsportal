<?php

namespace App\Modules\Faq\Models;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Support\Capabilities;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Eine Gruppe von Fragen auf der FAQ-Seite (z. B. „Zugang beantragen“). Im Portal pflegbar. */
class FaqCategory extends Model
{
    public const AUDIENCES = ['public' => 'Alle (öffentlich)', 'login' => 'Alle Angemeldeten', 'roles' => 'Bestimmte Rollen'];

    protected $fillable = ['title', 'description', 'audience', 'roles', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer', 'roles' => 'array'];
    }

    /** @return HasMany<FaqEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(FaqEntry::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Darf die Person die Kategorie sehen? Ausgeblendete Kategorien sieht niemand; sonst: öffentlich für alle, „login“ für Angemeldete, „roles“ für Konten mit einer der Rollen.
     * Wer die FAQ pflegt (faq.manage), sieht zur Kontrolle jede Kategorie.
     */
    public function isVisibleTo(?User $user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($this->audience) {
            'login' => $user !== null || $this->canBeSeenByEditors($user),
            'roles' => ($user !== null && array_intersect((array) $this->roles, Capabilities::activeRoleKeys($user)) !== []) || $this->canBeSeenByEditors($user),
            default => true,
        };
    }

    private function canBeSeenByEditors(?User $user): bool
    {
        return $user?->can('faq.manage') === true;
    }

    /** Für wen die Kategorie gedacht ist, als Text (öffentliche Kategorien: null). */
    public function audienceLabel(): ?string
    {
        return match ($this->audience) {
            'login' => 'Nur für Angemeldete',
            'roles' => 'Nur für '.collect((array) $this->roles)->map(fn (string $key): ?string => RoleKey::tryFrom($key)?->label())->filter()->implode(', '),
            default => null,
        };
    }

    /** @param Builder<FaqCategory> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
