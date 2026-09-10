<?php

namespace App\Modules\Membership\Support;

use App\Modules\Identity\Models\Person;
use App\Modules\Membership\Models\Membership;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class MembershipPeriodValidator
{
    /**
     * @param  array<string, mixed>  $values
     * @return array{starts_on: string, ends_on: ?string}
     *
     * @throws ValidationException
     */
    public function validate(array $values): array
    {
        /** @var array{starts_on: string, ends_on: ?string} $validated */
        $validated = Validator::make(
            $values,
            [
                'starts_on' => [
                    'required',
                    'date_format:Y-m-d',
                ],
                'ends_on' => [
                    'nullable',
                    'date_format:Y-m-d',
                    'after_or_equal:starts_on',
                ],
            ],
        )->validate();

        return $validated;
    }

    /**
     * @param  array{starts_on: string, ends_on: ?string}  $period
     *
     * @throws ValidationException
     */
    public function ensureNoOverlap(
        Person $person,
        array $period,
        ?Membership $ignore = null,
    ): void {
        $periodEnd = $period['ends_on'] ?? '9999-12-31';

        $query = Membership::query()
            ->where('person_id', $person->id)
            ->where('starts_on', '<=', $periodEnd)
            ->where(function ($query) use ($period): void {
                $query
                    ->whereNull('ends_on')
                    ->orWhere('ends_on', '>=', $period['starts_on']);
            });

        if ($ignore !== null) {
            $query->whereKeyNot($ignore->id);
        }

        if ($query->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([
                'starts_on' => [
                    'Der Mitgliedschaftszeitraum überschneidet sich mit einer vorhandenen Mitgliedschaft dieser Person.',
                ],
            ]);
        }
    }
}
