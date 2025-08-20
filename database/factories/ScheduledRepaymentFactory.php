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
            'amount' => $this->faker->numberBetween(100, 5000),
            'outstanding_amount' => function (array $attributes) {
                return $attributes['amount'];
            },
            'currency_code' => $this->faker->randomElement(['SGD', 'VND']),
            'due_date' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}
