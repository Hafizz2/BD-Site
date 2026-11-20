<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\StudentSource;
use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    private const FIRST_M = ['Abdulhafiz', 'Mohammed', 'Ahmed', 'Abdurahman', 'Yusuf', 'Ibrahim', 'Kedir', 'Nasir', 'Jemal', 'Seid', 'Hamza', 'Musa', 'Anwar', 'Ismail', 'Sultan'];

    private const FIRST_F = ['Hanan', 'Fatuma', 'Zeyneb', 'Hayat', 'Rahma', 'Meryem', 'Amina', 'Sumeya', 'Ikram', 'Ruqiya', 'Nejat', 'Hawa', 'Semira', 'Lubaba', 'Khadija'];

    private const LAST = ['Ali', 'Hussein', 'Mohammed', 'Abdella', 'Kemal', 'Yasin', 'Ahmed', 'Umer', 'Nuru', 'Shemsu', 'Redwan', 'Awol', 'Bedru', 'Jemal', 'Tahir'];

    public function definition(): array
    {
        $gender = $this->faker->randomElement(Gender::cases());
        $first = $this->faker->randomElement($gender === Gender::Male ? self::FIRST_M : self::FIRST_F);

        return [
            'full_name' => $first.' '.$this->faker->randomElement(self::LAST).' '.$this->faker->randomElement(self::LAST),
            'gender' => $gender,
            'date_of_birth' => $this->faker->dateTimeBetween('-26 years', '-17 years')->format('Y-m-d'),
            'phone' => '+2519'.$this->faker->unique()->numerify('########'),
            'email' => $this->faker->optional(0.4)->safeEmail(),
            'sub_city' => $this->faker->randomElement(['Bole', 'Yeka', 'Kirkos', 'Arada', 'Lideta', 'Kolfe Keranio', 'Addis Ketema']),
            'school' => $this->faker->randomElement(['Addis Ababa University', 'AASTU', 'Kotebe University', 'Unity University', 'Admas University']),
            'grade_or_year' => 'Year '.$this->faker->numberBetween(1, 5),
            'photo_consent' => $this->faker->boolean(70),
            'status' => StudentStatus::Active,
            'source' => $this->faker->randomElement(StudentSource::cases()),
            'registered_at' => $this->faker->dateTimeBetween('-1 year'),
            'locale' => $this->faker->randomElement(['am', 'en']),
        ];
    }

    public function minor(): static
    {
        return $this->state(fn () => [
            'date_of_birth' => $this->faker->dateTimeBetween('-16 years', '-9 years')->format('Y-m-d'),
            'guardian_name' => $this->faker->randomElement(self::FIRST_M).' '.$this->faker->randomElement(self::LAST),
            'guardian_phone' => '+2519'.$this->faker->numerify('########'),
            'guardian_relation' => 'Father',
            'guardian_consent' => true,
            'consent_at' => now(),
            'school' => 'Madrasa',
            'grade_or_year' => 'Grade '.$this->faker->numberBetween(3, 10),
        ]);
    }

    public function linked(): static
    {
        return $this->state(fn () => ['telegram_chat_id' => (string) $this->faker->unique()->numberBetween(100000000, 999999999), 'telegram_linked_at' => now()]);
    }
}
