<?php

namespace App\Modules\Membership\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\MembershipDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MembershipDocument extends Model
{
    protected $fillable = [
        'membership_id',
        'document_type',
        'label',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'sha256',
        'received_on',
        'uploaded_by_user_id',
        'supersedes_document_id',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => MembershipDocumentType::class,
            'received_on' => 'date',
            'size_bytes' => 'integer',
        ];
    }

    /** @return BelongsTo<Membership, $this> */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /** @return BelongsTo<MembershipDocument, $this> */
    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_document_id');
    }

    /** @return HasOne<MembershipDocument, $this> */
    public function supersededBy(): HasOne
    {
        return $this->hasOne(self::class, 'supersedes_document_id');
    }
}
