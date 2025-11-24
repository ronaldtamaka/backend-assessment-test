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
            'amount' => $this->faker->numberBetween(100, 1000),
            'currency_code' => $this->faker->randomElement([\App\Models\Loan::CURRENCY_SGD, \App\Models\Loan::CURRENCY_VND]),
            'received_at' => $this->faker->date(),
        ];
    }
}
