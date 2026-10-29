<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Enums\SeminarType;
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

    protected $fillable = ['title', 'type', 'partner_id', 'status', 'grade_level', 'expected_participants', 'notes', 'created_by_user_id', 'updated_by_user_id'];

    protected function casts(): array
    {
        return ['type' => SeminarType::class, 'status' => SeminarStatus::class];
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

    /** @return HasMany<SeminarQuote, $this> */
    public function quotes(): HasMany
    {
        return $this->hasMany(SeminarQuote::class)->orderByDesc('version');
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
