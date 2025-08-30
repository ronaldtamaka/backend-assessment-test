<?php

namespace Database\Factories;

use App\Models\Loan;
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
            'loan_id' => Loan::factory(),
            'amount' => $this->faker->numberBetween(100, 5000),
            'currency_code' => $this->faker->randomElement(['SGD', 'VND']),
            'received_at' => $this->faker->date(),
        ];
    }
}
