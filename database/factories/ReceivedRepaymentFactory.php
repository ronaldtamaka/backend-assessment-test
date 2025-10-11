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
    public function definition()
    {
        return [
            'loan_id' => Loan::factory(),
            'amount' => $this->faker->numberBetween(1000, 10000),
            'currency_code' => Loan::CURRENCY_VND,
            'received_at' => $this->faker->date(),
        ];
    }
}
