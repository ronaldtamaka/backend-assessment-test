<?php

namespace Database\Factories;

use App\Models\ReceivedRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceivedRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReceivedRepayment::class;

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
            'currency_code' => $this->faker->randomElement(['SGD', 'VND', 'IDR', 'THB']),
            'received_at' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
