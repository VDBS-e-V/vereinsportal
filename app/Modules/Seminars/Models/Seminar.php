<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarStatus;
use Database\Factories\SeminarFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Ein Seminar (oder eine Veranstaltungsreihe) einer Schule. Den Status ändert nur SeminarWorkflow. */
#[UseFactory(SeminarFactory::class)]
class Seminar extends Model
{
    /** @use HasFactory<SeminarFactory> */
    use HasFactory;

    protected $fillable = [
        'number', 'title', 'status', 'partner_id', 'coordination_id', 'school_year', 'format', 'participants', 'desired_period', 'request_note',
        'contact_name', 'contact_email', 'requested_by_user_id',
    ];

    protected function casts(): array
    {
        return ['status' => SeminarStatus::class, 'participants' => 'integer'];
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
