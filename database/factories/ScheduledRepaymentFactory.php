<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
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
    public function definition(): array
    {
        return [
            'loan_id' => \App\Models\Loan::factory(),
            'amount' => $this->faker->numberBetween(1000, 10000),
            'outstanding_amount' => function (array $attributes) {
                return $attributes['amount'];
            },
            'currency_code' => $this->faker->randomElement(['SGD', 'VND', 'IDR', 'THB']),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}
