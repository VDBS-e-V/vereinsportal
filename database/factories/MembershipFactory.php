<?php

namespace Database\Factories;

use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'status' => MembershipStatus::Active,
            'requested_on' => now()->subYear()->toDateString(),
            'starts_on' => now()->subYear()->toDateString(),
            'ends_on' => null,
        ];
    }

    /** Mitgliedschaft ohne digitale Person (vom Vorstand erfasst). */
    public function manual(): static
    {
        return $this->state(fn (): array => [
            'person_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'email' => fake()->unique()->safeEmail(),
            'source' => 'manual',
        ]);
    }

    public function requested(): static
    {
        return $this->state(['status' => MembershipStatus::Requested, 'requested_on' => now()->toDateString(), 'starts_on' => null]);
    }

    public function ended(): static
    {
        return $this->state(['status' => MembershipStatus::Ended, 'starts_on' => now()->subYears(3)->toDateString(), 'ends_on' => now()->subYear()->toDateString()]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => MembershipStatus::Rejected, 'starts_on' => null]);
    }
}
