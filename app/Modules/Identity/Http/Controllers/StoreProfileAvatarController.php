<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\Profile\StoreProfileAvatarAction;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class StoreProfileAvatarController
{
    public function __invoke(
        Request $request,
        StoreProfileAvatarAction $storeAvatar,
    ): RedirectResponse {
        $user = $request->user();
        $avatar = $request->file('avatar');

        abort_unless($user instanceof User, 403);
        abort_unless($avatar instanceof UploadedFile, 422);

        $storeAvatar->execute(
            user: $user,
            avatar: $avatar,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('my.account.profile')
            ->with(
                'avatar_status',
                'Ihr Profilbild wurde gespeichert.',
            );
    }
}
