<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Batch;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Registration> */
class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'full_name' => $this->faker->randomElement(['Hamza', 'Ikram', 'Seid', 'Nejat', 'Anwar']).' '.$this->faker->randomElement(['Ali', 'Kemal', 'Umer']),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'date_of_birth' => $this->faker->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),
            'phone' => '+2519'.$this->faker->unique()->numerify('########'),
            'school' => 'Addis Ababa University',
            'status' => RegistrationStatus::Pending,
            'locale' => 'am',
        ];
    }
}
