<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

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
    public function definition()
    {
        $amount = $this->faker->numberBetween(1000, 5000);

        return [
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'currency_code' => 'VND',
            'status' => ScheduledRepayment::STATUS_DUE,
            'due_date' => now()->addMonth(),
        ];
    }
}