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
    public function definition(): array
    {
        return [
            // TODO: Complete factory
            'user_id' => User::factory(),
            'amount' => $this->faker->numberBetween(5000, 10000),
            'terms' => $this->faker->randomElement([3, 6, 12]),
            'currency_code' => Loan::CURRENCY_VND,
            'processed_at' => $this->faker->dateTimeThisYear(),
            'status' => Loan::STATUS_DUE,
            'outstanding_amount' => function (array $attributes) {
                return $attributes['amount'];
            },
        ];
    }
}
