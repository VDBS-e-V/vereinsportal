<?php

namespace App\Modules\Identity\Support;

final class EmailNormalizer
{
    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }
}
