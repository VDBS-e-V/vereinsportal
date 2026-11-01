<?php

namespace App\Modules\Access\Models;

use App\Modules\Access\Enums\ProfileVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Das Profil eines Kontos: was die Person anderen zeigen möchte (Anzeigename, Funktion, Kurzvorstellung, Foto, Kontaktwege).
 * Stammdaten bleiben bei der Person. Das Foto liegt am Konto (users.avatar_path). Ohne Entscheidung ist jede Angabe privat.
 *
 * @property array<string, string>|null $visibility
 */
class UserProfile extends Model
{
    protected $fillable = ['user_id', 'display_name', 'headline', 'bio', 'visibility', 'consent_at'];

    protected function casts(): array
    {
        return ['visibility' => 'array', 'consent_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visibilityOf(string $field): ProfileVisibility
    {
        return ProfileVisibility::tryFrom((string) ($this->visibility[$field] ?? '')) ?? ProfileVisibility::Private;
    }
}
