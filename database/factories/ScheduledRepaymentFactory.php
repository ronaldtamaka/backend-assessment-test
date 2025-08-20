<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledRepaymentFactory extends Factory
{
    protected $model = ScheduledRepayment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'amount' => 1000, 
            'outstanding_amount' => 1000, 
            'currency_code' => 'VND',
            'due_date' => $this->faker->dateTimeBetween('+1 month', '+3 months')->format('Y-m-d'),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}
