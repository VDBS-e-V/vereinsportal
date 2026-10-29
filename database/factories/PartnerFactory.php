<?php

namespace Database\Factories;

use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\PartnerType;
use App\Modules\Partners\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'type' => PartnerType::School,
            'name' => fake()->unique()->company().' Schule',
            'city' => fake()->city(),
            'country_code' => 'DE',
            'status' => PartnerStatus::Active,
            'attributes' => ['school_type' => 'gymnasium', 'federal_state' => 'SN'],
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => PartnerStatus::Inactive]);
    }
}
