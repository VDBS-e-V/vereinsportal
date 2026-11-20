<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Partners\Models\Partner;
use App\Modules\Quotes\Models\SeminarQuote;
use App\Modules\Seminars\Enums\SeminarStatus;
use Database\Factories\SeminarFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Ein Seminar (oder eine Veranstaltungsreihe) einer Schule. Den Status ändert nur SeminarWorkflow. */
#[UseFactory(SeminarFactory::class)]
class Seminar extends Model
{
    /** @use HasFactory<SeminarFactory> */
    use HasFactory;

    protected $fillable = [
        'number', 'title', 'status', 'partner_id', 'coordination_id', 'school_year', 'format', 'participants', 'desired_period', 'request_note',
        'contact_name', 'contact_email', 'requested_by_user_id', 'release_basis', 'release_reason', 'released_at', 'released_by_user_id', 'school_contract_signed_on', 'team_target', 'application_deadline', 'lead_user_id', 'team_confirmed_at', 'team_confirmed_by_user_id',
        'held_at', 'report_due_on', 'reported_at', 'results_url', 'results_expires_on', 'report_sent_at', 'completed_at',
        'nextcloud_path', 'nextcloud_share_id', 'nextcloud_synced_at', 'nextcloud_error', 'nextcloud_manual',
    ];

    protected function casts(): array
    {
        return ['status' => SeminarStatus::class, 'participants' => 'integer', 'released_at' => 'datetime', 'school_contract_signed_on' => 'date', 'team_target' => 'integer', 'application_deadline' => 'date', 'team_confirmed_at' => 'datetime', 'held_at' => 'datetime', 'report_due_on' => 'date', 'reported_at' => 'datetime', 'results_expires_on' => 'date', 'report_sent_at' => 'datetime', 'completed_at' => 'datetime', 'onboarding_at' => 'datetime', 'briefing_sent_at' => 'datetime', 'tender_reminded_at' => 'datetime', 'report_reminded_at' => 'datetime', 'report_overdue_notified_at' => 'datetime', 'nextcloud_synced_at' => 'datetime', 'nextcloud_manual' => 'boolean', 'results_expiry_notified_at' => 'datetime'];
    }

    /** @return BelongsTo<Partner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /** @return BelongsTo<Coordination, $this> */
    public function coordination(): BelongsTo
    {
        return $this->belongsTo(Coordination::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /** @return HasMany<SeminarSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(SeminarSession::class)->orderBy('starts_at');
    }

    /** @return BelongsTo<User, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_user_id');
    }

    /** @return HasMany<PaymentOrder, $this> */
    public function paymentOrders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class)->orderBy('id');
    }

    /** @return HasOne<SeminarPrintPlan, $this> */
    public function printPlan(): HasOne
    {
        return $this->hasOne(SeminarPrintPlan::class);
    }

    /** @return HasMany<SeminarApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(SeminarApplication::class)->orderBy('id');
    }

    /** @return HasMany<SeminarDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(SeminarDocument::class)->orderBy('id');
    }

    /** @return HasMany<SeminarQuote, $this> */
    public function quotes(): HasMany
    {
        return $this->hasMany(SeminarQuote::class)->orderByDesc('version');
    }

    /** @return HasMany<SeminarEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(SeminarEvent::class)->orderByDesc('id');
    }

    /** Schuljahr zu einem Datum: ab August das neue Schuljahr (z. B. 2026/27). */
    public static function schoolYearOf(\DateTimeInterface $date): string
    {
        $year = (int) $date->format('Y');
        $start = (int) $date->format('n') >= 8 ? $year : $year - 1;

        return $start.'/'.substr((string) ($start + 1), 2);
    }
}
