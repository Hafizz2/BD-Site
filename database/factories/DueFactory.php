<?php

namespace Database\Factories;

use App\Enums\DueStatus;
use App\Models\Due;
use App\Models\FeePlan;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Due> */
class DueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payable_type' => 'student',
            'payable_id' => Student::factory(),
            'fee_plan_id' => FeePlan::factory(),
            'period' => now()->format('Y-m'),
            'description' => 'Training fee',
            'amount' => '500.00',
            'paid_amount' => '0.00',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => DueStatus::Unpaid,
        ];
    }
}
