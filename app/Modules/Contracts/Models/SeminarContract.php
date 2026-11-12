<?php

namespace App\Modules\Contracts\Models;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\SeminarDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Contracts. Ein Dienstleistungsvertrag für eine bestätigte Person eines Seminars. Die Daten (auch Steuernummer und IBAN) liegen verschlüsselt. */
class SeminarContract extends Model
{
    public const STATUS_LABELS = ['created' => 'Erzeugt', 'signed' => 'Unterschrieben', 'cancelled' => 'Storniert'];

    protected $fillable = ['number', 'seminar_id', 'application_id', 'user_id', 'template_id', 'template_version', 'data', 'status', 'signed_document_id', 'created_by_user_id', 'signed_at', 'retention_until'];

    protected function casts(): array
    {
        return ['data' => 'encrypted:array', 'signed_at' => 'datetime', 'retention_until' => 'date', 'template_version' => 'integer'];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return BelongsTo<SeminarApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(SeminarApplication::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ContractTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class);
    }

    /** @return BelongsTo<SeminarDocument, $this> */
    public function signedDocument(): BelongsTo
    {
        return $this->belongsTo(SeminarDocument::class, 'signed_document_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isEditable(): bool
    {
        return $this->status === 'created';
    }

    public function name(): string
    {
        return (string) ($this->data['name'] ?? '');
    }
}
