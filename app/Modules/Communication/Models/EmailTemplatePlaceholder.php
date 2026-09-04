<?php

namespace App\Modules\Communication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmailTemplatePlaceholder extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_template_id',
        'key',
        'label',
        'description',
        'example_value',
        'is_required',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(
            EmailTemplate::class,
            'email_template_id',
        );
    }
}
