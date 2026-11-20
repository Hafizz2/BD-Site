<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enrollment> */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'batch_id' => Batch::factory(),
            'enrolled_at' => now(),
            'status' => EnrollmentStatus::Approved,
        ];
    }
}
