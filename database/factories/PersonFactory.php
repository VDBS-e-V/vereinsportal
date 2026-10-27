<?php

namespace Database\Factories;

use App\Modules\People\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-16 years')->format('Y-m-d'),
            'email' => fake()->unique()->safeEmail(),
            'country_code' => 'DE',
        ];
    }
}
