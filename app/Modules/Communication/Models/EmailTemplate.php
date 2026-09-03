<?php

namespace App\Modules\Communication\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'is_active',
        'draft_subject',
        'draft_html',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by_user_id',
        );
    }

    public function versions(): HasMany
    {
        return $this->hasMany(
            EmailTemplateVersion::class
        );
    }

    public function placeholders(): HasMany
    {
        return $this->hasMany(
            EmailTemplatePlaceholder::class
        );
    }
}