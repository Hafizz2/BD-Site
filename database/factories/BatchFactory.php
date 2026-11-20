<?php

namespace Database\Factories;

use App\Enums\BatchSeason;
use App\Enums\BatchStatus;
use App\Models\Batch;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Batch> */
class BatchFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays($this->faker->numberBetween(-30, 60))->startOfDay();

        return [
            'program_id' => Program::factory(),
            'name' => 'Batch '.$this->faker->numberBetween(1, 20),
            'season' => $this->faker->randomElement(BatchSeason::cases()),
            'start_date' => $start,
            'end_date' => $start->copy()->addWeeks(8),
            'capacity' => 60,
            'registration_open' => true,
            'status' => BatchStatus::Active,
        ];
    }

    public function closed(): static
    {
        return $this->state(['registration_open' => false]);
    }
}
