<?php

namespace App\Modules\Onboarding\Services;

use App\Modules\Access\Models\User;
use App\Modules\Membership\Enums\MembershipEventType;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Services\MembershipHistory;
use App\Modules\Onboarding\Enums\OnboardingDocumentKind;
use App\Modules\Onboarding\Mail\MemberWelcomeMail;
use DomainException;
use Illuminate\Support\Facades\Mail;

/**
 * Onboarding der Mitglieder: Willkommenspaket per E-Mail (Willkommensmail mit Satzung und Willkommensbrief als Anhang, soweit hochgeladen).
 * Es geht automatisch beim Aufnehmen eines Online-Antrags und lässt sich vom Vorstand erneut senden. Das Senden steht im Verlauf der Mitgliedschaft.
 * Abschaltbar mit config('portal.onboarding.welcome_on_admission').
 */
final class MemberWelcome
{
    public function __construct(
        private readonly OnboardingDocuments $documents,
        private readonly MembershipHistory $history,
    ) {}

    /**
     * @return int Zahl der Anhänge
     *
     * @throws DomainException wenn keine E-Mail-Adresse bekannt ist
     */
    public function send(Membership $membership, ?User $by = null): int
    {
        $email = $membership->contactEmail();

        if (! filled($email)) {
            throw new DomainException('Für diese Mitgliedschaft ist keine E-Mail-Adresse hinterlegt.');
        }

        $attachments = array_values(array_filter(array_map(
            fn (OnboardingDocumentKind $kind) => $this->documents->current($kind),
            OnboardingDocumentKind::forMembers(),
        )));

        $membership->loadMissing('person.user');

        Mail::to($email)->send(new MemberWelcomeMail(
            $membership->displayName(),
            $membership->starts_on?->format('d.m.Y'),
            $membership->person?->user !== null ? route('login') : null,
            $attachments,
        ));

        $this->history->record($membership, MembershipEventType::WelcomeSent, $by, null, ['anhaenge' => count($attachments)], $by === null ? 'automatisch' : null);

        return count($attachments);
    }
}
