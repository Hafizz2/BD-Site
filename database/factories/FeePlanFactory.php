<?php

namespace Database\Factories;

use App\Enums\FeeAppliesTo;
use App\Enums\FeeFrequency;
use App\Models\FeePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeePlan> */
class FeePlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Training fee',
            'amount' => '500.00',
            'frequency' => FeeFrequency::PerBatch,
            'applies_to' => FeeAppliesTo::Batch,
            'due_in_days' => 14,
            'is_active' => true,
        ];
    }

    public function monthlyContribution(): static
    {
        return $this->state(['name' => 'Monthly member contribution', 'amount' => '200.00', 'frequency' => FeeFrequency::Monthly, 'applies_to' => FeeAppliesTo::MemberContribution, 'due_day' => 10, 'batch_id' => null]);
    }
}
