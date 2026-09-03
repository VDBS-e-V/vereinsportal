<?php

namespace App\Modules\Communication\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EmailTemplateVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_template_id',
        'version',
        'subject',
        'html',
        'published_by_user_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(
            EmailTemplate::class,
            'email_template_id',
        );
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'published_by_user_id',
        );
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(
            EmailDelivery::class,
            'template_version_id',
        );
    }
}