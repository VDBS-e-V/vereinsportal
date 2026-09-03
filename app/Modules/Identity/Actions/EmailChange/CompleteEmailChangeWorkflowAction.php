<?php

namespace App\Modules\Identity\Actions\EmailChange;

use App\Modules\Identity\Models\EmailChangeRequest;
use Throwable;

final class CompleteEmailChangeWorkflowAction
{
    public function __construct(
        private readonly CompleteEmailChangeAction $complete,
        private readonly QueueEmailChangeOldAddressNoticeAction $notice,
    ) {
    }

    public function execute(
        string $publicId,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): EmailChangeRequest {
        $request = $this->complete->execute(
            publicId: $publicId,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceInfo: $deviceInfo,
        );

        /*
         * Die fachliche E-Mail-Änderung bleibt committed,
         * selbst wenn die nachgelagerte Sicherheitsmail
         * nicht vorbereitet werden kann.
         */
        try {
            $this->notice->execute(
                $request
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return $request;
    }
}