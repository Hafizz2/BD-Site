<?php

namespace Database\Factories;

use App\Enums\MemberTeam;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Member> */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->randomElement(['Abdella', 'Yusuf', 'Hanan', 'Meryem', 'Nasir', 'Rahma', 'Kedir', 'Sumeya']).' '.$this->faker->randomElement(['Kemal', 'Ali', 'Hussein', 'Ahmed', 'Nuru']),
            'phone' => '+2519'.$this->faker->unique()->numerify('########'),
            'role_title' => $this->faker->randomElement(['Coordinator', 'Teacher', 'Volunteer', 'Media lead', 'Finance officer']),
            'team' => $this->faker->randomElement(MemberTeam::cases()),
            'bio' => $this->faker->sentence(16),
            'show_on_website' => $this->faker->boolean(50),
            'is_active' => true,
            'locale' => 'am',
        ];
    }

    public function linked(): static
    {
        return $this->state(fn () => ['telegram_chat_id' => (string) $this->faker->unique()->numberBetween(100000000, 999999999), 'telegram_linked_at' => now()]);
    }
}
