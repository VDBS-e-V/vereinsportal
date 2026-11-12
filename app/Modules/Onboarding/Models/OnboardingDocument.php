<?php

namespace App\Modules\Onboarding\Models;

use App\Modules\Access\Models\User;
use App\Modules\Onboarding\Enums\OnboardingDocumentKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Onboarding. Eine hochgeladene Datei (privater Speicher). Je Art gilt die jüngste. */
class OnboardingDocument extends Model
{
    public $timestamps = false;

    protected $fillable = ['kind', 'original_name', 'path', 'mime', 'size', 'sha256', 'uploaded_by_user_id', 'uploaded_at'];

    protected function casts(): array
    {
        return ['kind' => OnboardingDocumentKind::class, 'uploaded_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
