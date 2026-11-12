<?php

namespace Database\Factories;

use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seminar>
 */
class SeminarFactory extends Factory
{
    protected $model = Seminar::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => 'SV-Tage '.fake()->unique()->numerify('####'),
            'type' => SeminarType::SvDays,
            'partner_id' => Partner::factory(),
            'status' => SeminarStatus::Request,
        ];
    }
}
