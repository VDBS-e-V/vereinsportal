<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Support\EmailNormalizer;
use Illuminate\Contracts\View\View;

final class ShowPortalInvitationController extends Controller
{
    public function __invoke(string $publicId, string $version, string $token): View
    {
        abort_unless(ctype_digit($version), 410);

        $invitation = PortalInvitation::query()
            ->with('person.user')
            ->where('public_id', $publicId)
            ->firstOrFail();

        $isValid = $invitation->isOpen()
            && $invitation->token_version === (int) $version
            && hash_equals($invitation->token_hash, hash('sha256', $token))
            && $invitation->person->user === null
            && EmailNormalizer::normalize((string) $invitation->person->email) === $invitation->email;

        abort_unless($isValid, 410);

        return view('identity.portal-invitation', [
            'invitation' => $invitation,
            'person' => $invitation->person,
            'formAction' => request()->fullUrl(),
        ]);
    }
}
