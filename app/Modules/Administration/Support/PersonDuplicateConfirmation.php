<?php

namespace App\Modules\Administration\Support;

use App\Modules\Identity\Support\EmailNormalizer;
use JsonException;

final class PersonDuplicateConfirmation
{
    /**
     * @param  array{first_name: string, last_name: string, birth_date: string, email: string}  $personData
     * @param  list<int>  $personIds
     *
     * @throws JsonException
     */
    public function issue(
        array $personData,
        array $personIds,
    ): string {
        return hash_hmac(
            'sha256',
            $this->payload($personData, $personIds),
            (string) config('app.key'),
        );
    }

    /**
     * @param  array{first_name: string, last_name: string, birth_date: string, email: string}  $personData
     * @param  list<int>  $personIds
     *
     * @throws JsonException
     */
    public function matches(
        ?string $confirmation,
        array $personData,
        array $personIds,
    ): bool {
        if ($confirmation === null || $confirmation === '') {
            return false;
        }

        return hash_equals(
            $this->issue($personData, $personIds),
            $confirmation,
        );
    }

    /**
     * @param  array{first_name: string, last_name: string, birth_date: string, email: string}  $personData
     * @param  list<int>  $personIds
     *
     * @throws JsonException
     */
    private function payload(
        array $personData,
        array $personIds,
    ): string {
        sort($personIds, SORT_NUMERIC);

        return json_encode(
            [
                'version' => 1,
                'first_name' => $personData['first_name'],
                'last_name' => $personData['last_name'],
                'birth_date' => $personData['birth_date'],
                'email' => EmailNormalizer::normalize(
                    $personData['email'],
                ),
                'person_ids' => $personIds,
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );
    }
}
