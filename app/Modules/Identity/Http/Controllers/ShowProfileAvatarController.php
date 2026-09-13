<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ShowProfileAvatarController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 404);

        $path = $user->avatar_path;

        abort_unless(
            is_string($path)
                && $path !== ''
                && Storage::disk('local')->exists($path),
            404,
        );

        return response()->file(
            Storage::disk('local')->path($path),
            [
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
