<?php

namespace App\Modules\Membership\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Mail\NewApplicationMail;
use App\Modules\Membership\Models\Membership;
use Illuminate\Support\Facades\Mail;

/**
 * Informiert den Vorstand über neue Online-Anträge: einmal je Antrag, sobald die Antragstellerin ihre E-Mail-Adresse bestätigt
 * hat (so lösen unbestätigte Fremdanträge keine Nachricht aus). Empfänger sind aktive Konten mit gültiger Rolle Vorstand.
 * Abschaltbar mit config('portal.membership.notify_board').
 */
final class BoardNotifications
{
    public function newApplication(User $applicant): void
    {
        if (! config('portal.membership.notify_board') || $applicant->person_id === null) {
            return;
        }

        $membership = Membership::query()
            ->where('person_id', $applicant->person_id)
            ->where('source', 'online')
            ->where('status', MembershipStatus::Requested->value)
            ->whereNull('board_notified_at')
            ->first();

        if (! $membership instanceof Membership) {
            return;
        }

        $membership->forceFill(['board_notified_at' => now()])->save();

        $url = route('membership.show', $membership);

        User::query()
            ->where('status', UserStatus::Active->value)
            ->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', RoleKey::BoardMember->value)))
            ->get()
            ->each(fn (User $board) => Mail::to($board->email)->send(new NewApplicationMail($url)));
    }
}
