<?php

namespace App\Modules\Quotes\Models;

use App\Modules\Access\Models\User;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Eine Version des Kostenvoranschlags zu einem Seminar. */
class SeminarQuote extends Model
{
    protected $fillable = [
        'seminar_id', 'version', 'status', 'vat_percent', 'intro', 'outro', 'net_cents', 'vat_cents', 'total_cents',
        'created_by_user_id', 'approved_by_user_id', 'approved_at', 'sent_at', 'decided_at', 'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class, 'vat_percent' => 'decimal:1', 'net_cents' => 'integer', 'vat_cents' => 'integer', 'total_cents' => 'integer',
            'approved_at' => 'datetime', 'sent_at' => 'datetime', 'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Seminar, $this> */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /** @return HasMany<SeminarQuoteLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(SeminarQuoteLine::class)->orderBy('position');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
