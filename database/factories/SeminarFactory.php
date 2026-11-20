<?php

namespace Database\Factories;

use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Support\SeminarNumbers;
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
            'number' => SeminarNumbers::next(),
            'title' => 'Demokratie im Alltag',
            'status' => SeminarStatus::Request,
            'partner_id' => Partner::factory(),
            'format' => 'Projekttag',
            'participants' => 25,
            'contact_name' => 'Frau Lehrkraft',
            'contact_email' => 'lehrkraft@schule.example',
        ];
    }

    public function status(SeminarStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
