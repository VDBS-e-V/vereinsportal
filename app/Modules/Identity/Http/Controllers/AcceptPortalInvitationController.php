<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Actions\PortalInvitation\CompletePortalInvitationAction;
use App\Modules\Identity\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AcceptPortalInvitationController extends Controller
{
    public function __invoke(
        Request $request,
        string $publicId,
        string $version,
        string $token,
        CompletePortalInvitationAction $completeInvitation,
    ): RedirectResponse {
        abort_unless(ctype_digit($version), 410);

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                PasswordRules::default(),
            ],
        ]);

        $completeInvitation->execute(
            publicId: $publicId,
            version: (int) $version,
            token: $token,
            password: $validated['password'],
        );

        return redirect()
            ->route('my.login')
            ->with('status', 'Ihr Portalzugang wurde eingerichtet. Sie können sich jetzt anmelden.')
            ->with('status_type', 'success');
    }
}
