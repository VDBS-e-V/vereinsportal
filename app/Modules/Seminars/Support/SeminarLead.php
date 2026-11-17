<?php

namespace App\Modules\Seminars\Support;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Support\Collection;

/** Die Seminarleitung: die Person im bestätigten Team, die die Koordination als Leitung bestimmt hat. Sie sieht und bearbeitet nur das eigene Seminar. */
final class SeminarLead
{
    public static function is(?User $user, Seminar $seminar): bool
    {
        return $user !== null && $seminar->applications()->where('user_id', $user->id)->where('status', ApplicationStatus::Confirmed->value)->where('is_lead', true)->exists();
    }

    /** Seminare, die die Person leitet, ab „Team bestätigt“. @return Collection<int, Seminar> */
    public static function seminars(User $user): Collection
    {
        return Seminar::query()->with(['partner', 'sessions'])
            ->whereHas('applications', fn ($query) => $query->where('user_id', $user->id)->where('status', ApplicationStatus::Confirmed->value)->where('is_lead', true))
            ->whereIn('status', SeminarStatus::values([SeminarStatus::TeamConfirmed, SeminarStatus::Prepared, SeminarStatus::Held, SeminarStatus::Reported, SeminarStatus::Completed]))
            ->orderByDesc('updated_at')->get();
    }
}
