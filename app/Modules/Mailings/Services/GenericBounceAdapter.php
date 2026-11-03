<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Mailings\Contracts\BounceAdapter;

/** Neutrales Format: {"events":[{"type":"hard_bounce","email":"a@b.example"}]} oder ein einzelnes Ereignis. Für einen Versanddienst wird eine eigene Umsetzung ergänzt. */
final class GenericBounceAdapter implements BounceAdapter
{
    private const TYPES = ['hard_bounce', 'soft_bounce', 'complaint'];

    public function parse(string $body): array
    {
        $data = json_decode($body, true);

        if (! is_array($data)) {
            return [];
        }

        $events = isset($data['events']) && is_array($data['events']) ? $data['events'] : [$data];
        $result = [];

        foreach ($events as $event) {
            $type = is_array($event) ? (string) ($event['type'] ?? '') : '';
            $email = is_array($event) ? mb_strtolower(trim((string) ($event['email'] ?? ''))) : '';

            if (in_array($type, self::TYPES, true) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                $result[] = ['type' => $type, 'email' => $email];
            }
        }

        return $result;
    }
}
