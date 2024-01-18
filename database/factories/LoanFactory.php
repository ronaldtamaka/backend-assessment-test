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
            'user_id' => fn() => User::factory()->create(),
            'amount' => $this->faker->randomNumber(),
            'terms' => $this->faker->randomNumber(),
            'outstanding_amount' => $this->faker->randomNumber(),
            'currency_code' => Loan::CURRENCY_SGD,
            'processed_at' => $this->faker->dateTime,
            'status' => Loan::STATUS_DUE,
        ];
    }
}
