<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\Registration\CompleteRegistrationAction;
use App\Modules\Identity\Exceptions\RegistrationCannotComplete;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyRegistrationController
{
    public function __invoke(
        Request $request,
        string $publicId,
        int $version,
        CompleteRegistrationAction $completeRegistration,
    ): Response {
        try {
            $completeRegistration->execute(
                publicId: $publicId,
                version: $version,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (RegistrationCannotComplete $exception) {
            return response()->view(
                'identity.registration.verification-failed',
                [
                    'message' => $exception->getMessage(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return response()->view(
            'identity.registration.verified',
            status: Response::HTTP_OK,
        );
    }
}
