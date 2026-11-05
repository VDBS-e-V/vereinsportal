<?php

namespace App\Modules\Forms\Models;

use App\Modules\Access\Models\User;
use App\Modules\Forms\Support\FormTypes;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Forms. Ein Antrag (Kostenrückerstattung, Fahrtkosten, Materialbestellung) mit gemeinsamem Ablauf. Die Tabelle heißt form_requests. */
class FormApplication extends Model
{
    protected $table = 'form_requests';

    protected $guarded = [];

    public const STATUS_LABELS = [
        'draft' => 'Entwurf', 'submitted' => 'Eingereicht', 'in_review' => 'In Prüfung', 'returned' => 'Rückfrage', 'approved' => 'Freigegeben',
        'rejected' => 'Abgelehnt', 'paid' => 'Ausgezahlt', 'ordered' => 'Bestellt', 'delivered' => 'Geliefert', 'closed' => 'Abgeschlossen',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime', 'checked_at' => 'datetime', 'approved_at' => 'datetime', 'paid_at' => 'datetime', 'exported_at' => 'datetime',
            'retention_until' => 'date', 'data' => 'array', 'total_cents' => 'integer', 'self_checked' => 'boolean',
            'iban_encrypted' => 'encrypted', 'account_holder' => 'encrypted',
        ];
    }

    /** @return HasMany<FormItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(FormItem::class, 'request_id')->orderBy('position');
    }

    /** @return HasMany<FormFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(FormFile::class, 'request_id');
    }

    /** @return HasMany<FormEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(FormEvent::class, 'request_id')->orderBy('occurred_at')->orderBy('id');
    }

    /** @return BelongsTo<User, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public function typeLabel(): string
    {
        return FormTypes::label($this->type);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'returned'], true);
    }

    public function euro(): string
    {
        return number_format($this->total_cents / 100, 2, ',', '.').' €';
    }
}
