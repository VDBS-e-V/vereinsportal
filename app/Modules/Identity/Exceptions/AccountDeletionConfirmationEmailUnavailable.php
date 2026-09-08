<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;
use Throwable;

final class AccountDeletionConfirmationEmailUnavailable extends RuntimeException
{
    public function __construct(
        public readonly string $deletionPublicId,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            'Die Bestätigungs-E-Mail konnte derzeit nicht vorbereitet werden.',
            previous: $previous,
        );
    }
}
