<?php

namespace App\Modules\Membership\Listeners;

use App\Modules\Access\Events\AccountAnonymizing;
use App\Modules\Membership\Enums\MembershipEventType;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipEvent;
use App\Modules\Membership\Services\MembershipDocuments;
use App\Modules\Membership\Services\MembershipHistory;
use Illuminate\Support\Facades\Storage;

/**
 * Beim Löschen eines Kontos: laufende Mitgliedschaften enden, offene Anträge werden abgelehnt, Dokumente und persönliche
 * Notizen werden gelöscht, die gespeicherten Namens- und Adressangaben entfernt. Datum und Status bleiben (ohne Personenbezug).
 */
final class CleanUpMembershipsOnAccountDeletion
{
    public function __construct(private readonly MembershipHistory $history) {}

    public function handle(AccountAnonymizing $event): void
    {
        if ($event->person === null) {
            return;
        }

        Membership::query()->where('person_id', $event->person->id)->get()->each(function (Membership $membership): void {
            $this->close($membership);

            foreach ($membership->documents()->get() as $document) {
                Storage::disk(MembershipDocuments::DISK)->delete($document->path);
                $document->delete();
            }

            MembershipEvent::query()->where('membership_id', $membership->id)->update(['comment' => null, 'data' => null, 'actor_label' => 'gelöscht']);

            $membership->forceFill([
                'first_name' => null,
                'last_name' => null,
                'birth_date' => null,
                'email' => null,
                'registration_token_hash' => null,
                'registration_expires_at' => null,
            ])->save();
        });
    }

    private function close(Membership $membership): void
    {
        if ($membership->status === MembershipStatus::Active && ($membership->ends_on === null || $membership->ends_on->gte(today()))) {
            $membership->forceFill(['status' => MembershipStatus::Ended, 'ends_on' => today()->subDay()->toDateString()])->save();
            $this->history->record($membership, MembershipEventType::Ended, null, 'Konto gelöscht', [], 'automatisch');
        } elseif ($membership->status === MembershipStatus::Requested) {
            $membership->forceFill(['status' => MembershipStatus::Rejected, 'decided_at' => now()])->save();
            $this->history->record($membership, MembershipEventType::Rejected, null, 'Konto gelöscht', [], 'automatisch');
        }
    }
}
