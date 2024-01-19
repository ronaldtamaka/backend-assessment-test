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
            // TODO: Complete factory
            'loan_id' => Loan::factory(),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'amount' => $this->faker->numberBetween(1000, 100000),
            'currency_code' => 'USD',
        ];
    }
}
