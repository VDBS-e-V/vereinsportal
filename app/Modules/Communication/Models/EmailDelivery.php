<?php

namespace App\Modules\Communication\Models;

use App\Modules\Communication\Enums\EmailDeliveryStatus;
use App\Modules\Communication\Enums\EmailDeliveryType;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmailDelivery extends Model
{
    protected $fillable = [
        'template_version_id',
        'sender_user_id',
        'recipient_email',
        'subject',
        'delivery_type',
        'status',
        'attempts',
        'queued_at',
        'sent_at',
        'failed_at',
        'last_error_class',
    ];

    protected function casts(): array
    {
        return [
            'delivery_type' => EmailDeliveryType::class,
            'status' => EmailDeliveryStatus::class,
            'attempts' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(
            EmailTemplateVersion::class,
            'template_version_id',
        );
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'sender_user_id',
        );
    }
}
