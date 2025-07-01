<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Loan;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
       return [
            'loan_id' => Loan::factory(),
            'amount' => 1666,
            'outstanding_amount' => 1666,
            'currency_code' => 'VND',
            'due_date' => now()->addMonth(),
            'status' => ScheduledRepayment::STATUS_DUE, 
        ];
    }
}
