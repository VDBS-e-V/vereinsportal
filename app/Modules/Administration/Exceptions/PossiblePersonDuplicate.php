<?php

namespace App\Modules\Administration\Exceptions;

use RuntimeException;

final class PossiblePersonDuplicate extends RuntimeException
{
    /**
     * @param  list<int>  $personIds
     */
    public function __construct(
        public readonly array $personIds,
    ) {
        parent::__construct(
            'Es wurden mögliche vorhandene Personen gefunden.',
        );
    }
}
