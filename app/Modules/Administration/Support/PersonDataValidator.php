<?php

namespace App\Modules\Administration\Support;

use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Support\EmailNormalizer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersonDataValidator
{
    /**
     * @param  array<string, mixed>  $values
     * @return array{
     *     title: ?string,
     *     first_name: string,
     *     name_addition: ?string,
     *     last_name: string,
     *     birth_date: string,
     *     email: string,
     *     phone: ?string,
     *     street: ?string,
     *     house_number: ?string,
     *     postal_code: ?string,
     *     city: ?string,
     *     country_code: string
     * }
     *
     * @throws ValidationException
     */
    public function validate(
        array $values,
        ?Person $person = null,
    ): array {
        $normalized = $this->normalize($values);

        $personEmailRule = Rule::unique(
            'persons',
            'email',
        );

        if ($person !== null) {
            $personEmailRule->ignore($person->id);
        }

        $userEmailRule = Rule::unique(
            'users',
            'email',
        );

        $linkedUser = $person?->user;

        if ($linkedUser !== null) {
            $userEmailRule->ignore($linkedUser->id);
        }

        /** @var array{
         *     title: ?string,
         *     first_name: string,
         *     name_addition: ?string,
         *     last_name: string,
         *     birth_date: string,
         *     email: string,
         *     phone: ?string,
         *     street: ?string,
         *     house_number: ?string,
         *     postal_code: ?string,
         *     city: ?string,
         *     country_code: string
         * } $validated
         */
        $validated = Validator::make(
            $normalized,
            [
                'title' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
                'first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'name_addition' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
                'last_name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'birth_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],
                'email' => [
                    'required',
                    'email',
                    'max:254',
                    $personEmailRule,
                    $userEmailRule,
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
                'street' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
                'house_number' => [
                    'nullable',
                    'string',
                    'max:30',
                ],
                'postal_code' => [
                    'nullable',
                    'string',
                    'max:20',
                ],
                'city' => [
                    'nullable',
                    'string',
                    'max:120',
                ],
                'country_code' => [
                    'required',
                    'string',
                    'size:2',
                    'alpha',
                ],
            ],
        )->validate();

        if (
            $person !== null
            && $linkedUser !== null
            && $validated['email']
                !== EmailNormalizer::normalize($person->email)
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'Die E-Mail-Adresse einer Person mit Benutzerkonto kann hier nicht geändert werden. Verwenden Sie dafür den E-Mail-Änderungsprozess des Kontos.',
                ],
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function normalize(array $values): array
    {
        return [
            'title' => $this->nullableString(
                $values['title'] ?? null,
            ),
            'first_name' => $this->trimString(
                $values['first_name'] ?? '',
            ),
            'name_addition' => $this->nullableString(
                $values['name_addition'] ?? null,
            ),
            'last_name' => $this->trimString(
                $values['last_name'] ?? '',
            ),
            'birth_date' => $this->trimString(
                $values['birth_date'] ?? '',
            ),
            'email' => $this->normalizeEmail(
                $values['email'] ?? '',
            ),
            'phone' => $this->nullableString(
                $values['phone'] ?? null,
            ),
            'street' => $this->nullableString(
                $values['street'] ?? null,
            ),
            'house_number' => $this->nullableString(
                $values['house_number'] ?? null,
            ),
            'postal_code' => $this->nullableString(
                $values['postal_code'] ?? null,
            ),
            'city' => $this->nullableString(
                $values['city'] ?? null,
            ),
            'country_code' => $this->normalizeCountryCode(
                $values['country_code'] ?? '',
            ),
        ];
    }

    private function trimString(mixed $value): mixed
    {
        return is_string($value)
            ? trim($value)
            : $value;
    }

    private function nullableString(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function normalizeEmail(mixed $value): mixed
    {
        return is_string($value)
            ? EmailNormalizer::normalize($value)
            : $value;
    }

    private function normalizeCountryCode(mixed $value): mixed
    {
        return is_string($value)
            ? strtoupper(trim($value))
            : $value;
    }
}
