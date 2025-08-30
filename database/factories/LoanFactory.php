<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->numberBetween(1000, 100000),
            'outstanding_amount' => function (array $attributes) {
                return $attributes['amount'];
            },
            'currency_code' => Loan::CURRENCY_IDR,
            'terms' => $this->faker->numberBetween(3, 12),
            'processed_at' => $this->faker->dateTimeThisYear(),
            'status' => Loan::STATUS_DUE,
        ];
    }
}
