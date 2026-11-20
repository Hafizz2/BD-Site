<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubmittedVia;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payable_type' => 'student',
            'payable_id' => Student::factory(),
            'amount' => '500.00',
            'method' => $this->faker->randomElement(PaymentMethod::cases()),
            'transaction_ref' => strtoupper($this->faker->bothify('??########')),
            'paid_at' => now(),
            'status' => PaymentStatus::PendingReview,
            'submitted_via' => SubmittedVia::Admin,
        ];
    }
}
