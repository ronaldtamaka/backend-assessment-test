<?php

namespace Database\Factories;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

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
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'terms' => $this->faker->numberBetween(3, 6),
            'amount' => $this->faker->numberBetween(1000, 10000),
            'outstanding_amount' => function (array $attributes) {
                return $attributes['amount'];
            }, 
            'currency_code' => Loan::CURRENCY_VND,
            'processed_at' => now(),
            'status' => Loan::STATUS_DUE,
        ];
    }
}
