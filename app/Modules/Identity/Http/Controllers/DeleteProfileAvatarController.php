<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\Profile\DeleteProfileAvatarAction;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DeleteProfileAvatarController
{
    public function __invoke(
        Request $request,
        DeleteProfileAvatarAction $deleteAvatar,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $deleteAvatar->execute(
            user: $user,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('my.account.profile')
            ->with(
                'avatar_status',
                'Ihr Profilbild wurde gelöscht.',
            );
    }
}
